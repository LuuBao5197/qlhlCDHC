<?php

namespace Modules\Schedule\Application\ReviewChangeRequest;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Modules\Training\Models\ChangeRequest;
use Throwable;

class ReviewChangeRequestHandler
{
    private const CHANGE_TYPE_HOLIDAY = 'holiday_reschedule';

    /**
     * @var array<int, array<int>>
     */
    private array $planMonthlyScheduleScopeCache = [];

    /**
     * Handle review for a schedule change request.
     */
    public function handle(ReviewChangeRequestRequest $request, int $id)
    {
        $validated = $request->validated();
        $isApproved = $validated['action'] === 'approve';

        try {
            $result = DB::transaction(function () use ($request, $id, $validated, $isApproved): array {
                $changeRequest = ChangeRequest::query()
                    ->whereKey($id)
                    ->lockForUpdate() // dam bao isolation (tinh co lap cua giao dich)
                    ->firstOrFail();

                if ($changeRequest->status !== 'pending') {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Phiếu đề nghị đã được tài khoản khác xử lý. Vui lòng tải lại danh sách.',
                    ];
                }

                $changeRequest->loadMissing(['scheduleSlot', 'changeRequestItems.scheduleSlot']);

                $now = now();
                $actorId = $request->user()->id;

                if (
                    $isApproved
                    && (string) ($changeRequest->change_type ?? 'general') === self::CHANGE_TYPE_HOLIDAY
                    && $changeRequest->requested_by !== null
                    && (int) $changeRequest->requested_by === (int) $actorId
                ) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Khong duoc tu phe duyet phieu doi lich nghi le/tet do chinh ban tao.',
                    ];
                }

                $applyChanges = (bool) ($validated['apply_changes'] ?? true);
                $applyMode = (string) ($validated['apply_mode'] ?? $changeRequest->apply_mode ?? 'all_or_none');
                $applySummary = null;

                if ($isApproved && $applyChanges) {
                    $applySummary = $this->applyItemsByMode($changeRequest, $applyMode);
                }

                if ($isApproved && ! $applyChanges) {
                    $applySummary = [
                        'mode' => $applyMode,
                        'attempted' => 0,
                        'applied' => 0,
                        'failed' => 0,
                        'errors' => ['Changes were approved without applying payload updates.'],
                    ];
                }

                $affected = ChangeRequest::query()
                    ->whereKey($changeRequest->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => $isApproved ? 'approved' : 'rejected',
                        'apply_mode' => $applyMode,
                        'apply_changes' => $applyChanges,
                        'apply_summary' => $applySummary,
                        'resolved_at' => $now,
                        'updated_at' => $now,
                    ]);

                if ($affected !== 1) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'Phiếu đề nghị đã được tài khoản khác xử lý. Vui lòng tải lại danh sách.',
                    ];
                }

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => ChangeRequest::class,
                    'entity_id' => $changeRequest->id,
                ]);

                if (! $approvalRequest->exists) {
                    $approvalRequest->submitted_by = $changeRequest->requested_by;
                    $approvalRequest->submitted_at = $changeRequest->submitted_at ?? $now;
                }

                $approvalRequest->current_step = 'training_review';
                $approvalRequest->status = $isApproved ? 'approved' : 'rejected';
                $approvalRequest->completed_at = $now;
                $approvalRequest->save();

                ApprovalAction::query()->create([
                    'approval_request_id' => $approvalRequest->id,
                    'step_code' => 'training_review',
                    'action' => $isApproved ? 'approve' : 'reject',
                    'acted_by' => $actorId,
                    'acted_at' => $now,
                    'comment' => $this->buildComment($validated),
                ]);

                return [
                    'ok' => true,
                    'change_request_id' => (int) $changeRequest->id,
                ];
            });

            if (! ($result['ok'] ?? false)) {
                return $this->errorResponse(
                    $request,
                    (string) ($result['message'] ?? 'Could not review change request.'),
                    (int) ($result['status'] ?? 422)
                );
            }

            return $this->successResponse(
                $request,
                (int) $result['change_request_id'],
                $isApproved
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Could not review change request. ' . $exception->getMessage(),
                500
            );
        }
    }

    private function applyItemsByMode(ChangeRequest $changeRequest, string $mode): array
    {
        $items = $changeRequest->changeRequestItems;

        if ($items->isEmpty()) {
            $applied = $this->applyNewPayloadToSlot($changeRequest);

            return [
                'mode' => $mode,
                'attempted' => 1,
                'applied' => $applied ? 1 : 0,
                'failed' => $applied ? 0 : 1,
                'errors' => $applied ? [] : ['Legacy slot payload could not be applied.'],
            ];
        }

        if ($mode === 'best_effort') {
            return $this->applyBestEffort($changeRequest);
        }

        return $this->applyAllOrNone($changeRequest);
    }

    private function applyAllOrNone(ChangeRequest $changeRequest): array
    {
        $items = $changeRequest->changeRequestItems;
        $now = now();
        $teacherAssignmentMap = [];
        $classAssignmentMap = [];
        $batchSlotIds = $items
            ->pluck('schedule_slot_id')
            ->filter(static fn($id) => is_numeric($id))
            ->map(static fn($id) => (int) $id)
            ->values()
            ->all();

        foreach ($items as $index => $item) {
            $payload = is_array($item->new_payload) ? $item->new_payload : null;
            $error = $this->validateClassScheduleCollision($item->scheduleSlot, $payload, $classAssignmentMap, $batchSlotIds);
            if ($error !== null) {
                throw new \RuntimeException(
                    'Failed to validate item #' . ($index + 1) . ' in all_or_none mode. ' . $error
                );
            }

            $error = $this->validateTeacherAssignment($item->scheduleSlot, $payload, $teacherAssignmentMap, $batchSlotIds);
            if ($error !== null) {
                throw new \RuntimeException(
                    'Failed to validate item #' . ($index + 1) . ' in all_or_none mode. ' . $error
                );
            }
        }

        $pendingItems = $items->values();
        $maxPasses = max(1, $pendingItems->count());
        for ($pass = 0; $pass < $maxPasses && $pendingItems->isNotEmpty(); $pass++) {
            $nextPendingItems = collect();
            $progress = false;

            foreach ($pendingItems as $item) {
                $isApplied = $this->applyPayloadToSlot(
                    $changeRequest,
                    $item->scheduleSlot,
                    is_array($item->new_payload) ? $item->new_payload : null,
                    $batchSlotIds
                );

                if ($isApplied) {
                    $progress = true;
                    continue;
                }

                $nextPendingItems->push($item);
            }

            if (! $progress) {
                break;
            }

            $pendingItems = $nextPendingItems;
        }

        if ($pendingItems->isNotEmpty()) {
            throw new \RuntimeException(
                'Failed to apply ' . $pendingItems->count() . ' item(s) in all_or_none mode due to unresolved collisions.'
            );
        }

        foreach ($items as $item) {
            $item->update([
                'apply_status' => 'applied',
                'apply_error' => null,
                'applied_at' => $now,
            ]);
        }

        return [
            'mode' => 'all_or_none',
            'attempted' => $items->count(),
            'applied' => $items->count(),
            'failed' => 0,
            'errors' => [],
        ];
    }

    private function applyBestEffort(ChangeRequest $changeRequest): array
    {
        $items = $changeRequest->changeRequestItems;
        $now = now();
        $applied = 0;
        $failed = 0;
        $errors = [];
        $teacherAssignmentMap = [];
        $classAssignmentMap = [];
        $batchSlotIds = $items
            ->pluck('schedule_slot_id')
            ->filter(static fn($id) => is_numeric($id))
            ->map(static fn($id) => (int) $id)
            ->values()
            ->all();
        $readyItems = collect();

        foreach ($items as $index => $item) {
            $payload = is_array($item->new_payload) ? $item->new_payload : null;
            $error = $this->validateClassScheduleCollision($item->scheduleSlot, $payload, $classAssignmentMap, $batchSlotIds);
            if ($error !== null) {
                $failed++;
                $errorMessage = 'Failed to validate item #' . ($index + 1) . ' in best_effort mode. ' . $error;
                $errors[] = $errorMessage;

                $item->update([
                    'apply_status' => 'failed',
                    'apply_error' => $errorMessage,
                    'applied_at' => null,
                ]);
                continue;
            }

            $error = $this->validateTeacherAssignment($item->scheduleSlot, $payload, $teacherAssignmentMap, $batchSlotIds);
            if ($error !== null) {
                $failed++;
                $errorMessage = 'Failed to validate item #' . ($index + 1) . ' in best_effort mode. ' . $error;
                $errors[] = $errorMessage;

                $item->update([
                    'apply_status' => 'failed',
                    'apply_error' => $errorMessage,
                    'applied_at' => null,
                ]);
                continue;
            }

            $readyItems->push([
                'index' => $index,
                'item' => $item,
                'payload' => $payload,
            ]);
        }

        $pendingItems = $readyItems->values();
        $maxPasses = max(1, $pendingItems->count());
        for ($pass = 0; $pass < $maxPasses && $pendingItems->isNotEmpty(); $pass++) {
            $nextPendingItems = collect();
            $progress = false;

            foreach ($pendingItems as $entry) {
                $item = $entry['item'];
                $payload = $entry['payload'];

                $isApplied = $this->applyPayloadToSlot(
                    $changeRequest,
                    $item->scheduleSlot,
                    $payload,
                    $batchSlotIds
                );

                if ($isApplied) {
                    $applied++;
                    $progress = true;
                    $item->update([
                        'apply_status' => 'applied',
                        'apply_error' => null,
                        'applied_at' => $now,
                    ]);
                    continue;
                }

                $nextPendingItems->push($entry);
            }

            if (! $progress) {
                break;
            }

            $pendingItems = $nextPendingItems;
        }

        foreach ($pendingItems as $entry) {
            $failed++;
            $index = (int) $entry['index'];
            $item = $entry['item'];
            $errorMessage = 'Failed to apply item #' . ($index + 1) . ' in best_effort mode due to unresolved collisions.';
            $errors[] = $errorMessage;

            $item->update([
                'apply_status' => 'failed',
                'apply_error' => $errorMessage,
                'applied_at' => null,
            ]);
        }

        return [
            'mode' => 'best_effort',
            'attempted' => $items->count(),
            'applied' => $applied,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    private function applyNewPayloadToSlot(ChangeRequest $changeRequest): bool
    {
        return $this->applyPayloadToSlot(
            $changeRequest,
            $changeRequest->scheduleSlot,
            is_array($changeRequest->new_payload) ? $changeRequest->new_payload : null,
            []
        );
    }

    /**
     * @param array<int> $batchSlotIds
     */
    private function applyPayloadToSlot(
        ChangeRequest $changeRequest,
        ?ScheduleSlot $slot,
        ?array $payload,
        array $batchSlotIds = []
    ): bool
    {
        if ($slot === null || $payload === null) {
            return false;
        }

        $filteredPayload = $this->getAllowedSlotPayload($payload, (string) ($changeRequest->change_type ?? 'general'));

        if ($filteredPayload === []) {
            return false;
        }

        $candidateDate = $this->resolveEffectiveDate($slot, $filteredPayload);
        $candidatePeriodNumber = $this->resolveEffectivePeriodNumber($slot, $filteredPayload);

        if ($candidateDate === null || $candidatePeriodNumber === null) {
            return false;
        }

        $classId = $this->normalizeNullableNumber($slot->class_id);
        if ($classId !== null) {
            $planScopedMonthlyScheduleIds = $this->resolvePlanScopedMonthlyScheduleIds($slot);
            $hasClassCollision = ScheduleSlot::query()
                ->whereIn('monthly_schedule_id', $planScopedMonthlyScheduleIds)
                ->where('class_id', $classId)
                ->whereDate('date', $candidateDate)
                ->where('period_number', $candidatePeriodNumber)
                ->where('id', '!=', $slot->id)
                ->when(
                    $batchSlotIds !== [],
                    static fn($query) => $query->whereNotIn('id', $batchSlotIds)
                )
                ->exists();

            if ($hasClassCollision) {
                return false;
            }
        }

        $candidateTeacherId = array_key_exists('teacher_id', $filteredPayload)
            ? $this->normalizeNullableNumber($filteredPayload['teacher_id'])
            : $this->normalizeNullableNumber($slot->teacher_id);

        if ($candidateTeacherId !== null) {
            $hasTeacherCollision = ScheduleSlot::query()
                ->where('teacher_id', $candidateTeacherId)
                ->whereDate('date', $candidateDate)
                ->where('period_number', $candidatePeriodNumber)
                ->where('id', '!=', $slot->id)
                ->when(
                    $batchSlotIds !== [],
                    static fn($query) => $query->whereNotIn('id', $batchSlotIds)
                )
                ->exists();

            if ($hasTeacherCollision) {
                return false;
            }
        }

        try {
            $slot->fill($filteredPayload);

            if ($slot->isDirty()) {
                $slot->save();
            }
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, int> $assignmentMap
     * @param array<int> $batchSlotIds
     */
    private function validateTeacherAssignment(?ScheduleSlot $slot, ?array $payload, array &$assignmentMap, array $batchSlotIds): ?string
    {
        if ($slot === null) {
            return 'Target slot is missing.';
        }

        $effectiveDate = $this->resolveEffectiveDate($slot, $payload);
        $effectivePeriodNumber = $this->resolveEffectivePeriodNumber($slot, $payload);
        if ($effectiveDate === null || $effectivePeriodNumber === null) {
            return 'Target date or period is invalid.';
        }

        $candidateTeacherId = array_key_exists('teacher_id', $payload ?? [])
            ? $this->normalizeNullableNumber($payload['teacher_id'])
            : $this->normalizeNullableNumber($slot->teacher_id);

        if ($candidateTeacherId === null) {
            return null;
        }

        $assignmentKey = $candidateTeacherId . '|' . $effectiveDate . '|' . $effectivePeriodNumber;
        if (isset($assignmentMap[$assignmentKey]) && $assignmentMap[$assignmentKey] !== (int) $slot->id) {
            return 'Teacher conflict within selected request items.';
        }

        $assignmentMap[$assignmentKey] = (int) $slot->id;

        $hasExistingConflict = ScheduleSlot::query()
            ->where('teacher_id', $candidateTeacherId)
            ->whereDate('date', $effectiveDate)
            ->where('period_number', $effectivePeriodNumber)
            ->where('id', '!=', $slot->id)
            ->when(
                $batchSlotIds !== [],
                static fn($query) => $query->whereNotIn('id', $batchSlotIds)
            )
            ->exists();

        if ($hasExistingConflict) {
            return 'Teacher already has another slot at the same date and period.';
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, int> $assignmentMap
     * @param array<int> $batchSlotIds
     */
    private function validateClassScheduleCollision(?ScheduleSlot $slot, ?array $payload, array &$assignmentMap, array $batchSlotIds): ?string
    {
        if ($slot === null) {
            return 'Target slot is missing.';
        }

        $classId = $this->normalizeNullableNumber($slot->class_id);
        if ($classId === null) {
            return null;
        }

        $effectiveDate = $this->resolveEffectiveDate($slot, $payload);
        $effectivePeriodNumber = $this->resolveEffectivePeriodNumber($slot, $payload);
        if ($effectiveDate === null || $effectivePeriodNumber === null) {
            return 'Target date or period is invalid.';
        }

        $assignmentKey = $classId . '|' . $effectiveDate . '|' . $effectivePeriodNumber;
        if (isset($assignmentMap[$assignmentKey]) && $assignmentMap[$assignmentKey] !== (int) $slot->id) {
            return 'Class already has another slot in the same date and period within request items.';
        }

        $assignmentMap[$assignmentKey] = (int) $slot->id;

        $planScopedMonthlyScheduleIds = $this->resolvePlanScopedMonthlyScheduleIds($slot);
        $hasExistingCollision = ScheduleSlot::query()
            ->whereIn('monthly_schedule_id', $planScopedMonthlyScheduleIds)
            ->where('class_id', $classId)
            ->whereDate('date', $effectiveDate)
            ->where('period_number', $effectivePeriodNumber)
            ->where('id', '!=', $slot->id)
            ->when(
                $batchSlotIds !== [],
                static fn($query) => $query->whereNotIn('id', $batchSlotIds)
            )
            ->exists();

        if ($hasExistingCollision) {
            return 'Class already has another slot in the same date and period.';
        }

        return null;
    }

    /**
     * @return array<int>
     */
    private function resolvePlanScopedMonthlyScheduleIds(ScheduleSlot $slot): array
    {
        $monthlyScheduleId = is_numeric($slot->monthly_schedule_id) ? (int) $slot->monthly_schedule_id : 0;

        if ($monthlyScheduleId <= 0) {
            return [];
        }

        if (isset($this->planMonthlyScheduleScopeCache[$monthlyScheduleId])) {
            return $this->planMonthlyScheduleScopeCache[$monthlyScheduleId];
        }

        $planId = MonthlySchedule::query()
            ->whereKey($monthlyScheduleId)
            ->value('plan_id');

        if (! is_numeric($planId) || (int) $planId <= 0) {
            return $this->planMonthlyScheduleScopeCache[$monthlyScheduleId] = [$monthlyScheduleId];
        }

        $ids = MonthlySchedule::query()
            ->where('plan_id', (int) $planId)
            ->pluck('id')
            ->map(static fn($id) => (int) $id)
            ->values()
            ->all();

        if ($ids === []) {
            $ids = [$monthlyScheduleId];
        }

        return $this->planMonthlyScheduleScopeCache[$monthlyScheduleId] = $ids;
    }

    private function normalizeNullableNumber(mixed $value): ?int
    {
        if ($value === '' || $value === null || $value === false) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function getAllowedSlotPayload(array $payload, string $changeType = 'general'): array
    {
        $allowedKeys = [
            'teacher_id',
            'subject_id',
            'subject_lesson_id',
            'room_id',
            'content',
            'slot_status',
            'actual_content',
            'note',
        ];

        if ($changeType === self::CHANGE_TYPE_HOLIDAY) {
            $allowedKeys = array_merge($allowedKeys, [
                'date',
                'day_of_week',
                'period_number',
            ]);
        }

        return array_intersect_key($payload, array_flip($allowedKeys));
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function resolveEffectiveDate(ScheduleSlot $slot, ?array $payload): ?string
    {
        if (is_array($payload) && array_key_exists('date', $payload)) {
            return $this->normalizeDateString($payload['date']);
        }

        return $slot->date?->format('Y-m-d');
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function resolveEffectivePeriodNumber(ScheduleSlot $slot, ?array $payload): ?int
    {
        if (is_array($payload) && array_key_exists('period_number', $payload)) {
            $value = $payload['period_number'];

            return is_numeric($value) ? (int) $value : null;
        }

        return is_numeric($slot->period_number) ? (int) $slot->period_number : null;
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function buildComment(array $validated): ?string
    {
        $parts = array_filter([
            $validated['reason'] ?? null,
            $validated['comment'] ?? null,
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function successResponse(ReviewChangeRequestRequest $request, int $changeRequestId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Change request approved successfully.'
            : 'Change request rejected successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'change_request_id' => $changeRequestId,
            ]);
        }

        return redirect()->route('schedule.index')->with('success', $message);
    }

    private function errorResponse(ReviewChangeRequestRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        if ($status === 422 && str_contains($message, 'đã được tài khoản khác xử lý')) {
            return redirect()->route('schedule.index')->with('warning', $message);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
