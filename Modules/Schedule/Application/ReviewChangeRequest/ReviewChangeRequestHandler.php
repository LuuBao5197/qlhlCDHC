<?php

namespace Modules\Schedule\Application\ReviewChangeRequest;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Modules\Training\Models\ChangeRequest;
use Throwable;

class ReviewChangeRequestHandler
{
    private const CHANGE_TYPE_HOLIDAY = 'holiday_reschedule';
    private const PAYLOAD_ACTION_SPLIT_FROM_MERGED_GROUP = 'split_from_merged_group';

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

                $changeRequest->loadMissing(['scheduleSlot.scheduleSlotGroup', 'changeRequestItems.scheduleSlot.scheduleSlotGroup']);

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

                $applyChanges = $isApproved;
                $applyMode = 'all_or_none';
                $applySummary = null;

                if ($isApproved && $applyChanges) {
                    $applySummary = $this->applyItemsByMode($changeRequest, $applyMode);
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
                    throw new ReviewChangeRequestApplyException([
                        'Change request was already processed by another account. Please reload the list.',
                    ]);
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

                app(InternalNotificationService::class)->notifyScheduleChangeRequestReviewed(
                    $changeRequest,
                    $request->user(),
                    $isApproved
                );

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
        } catch (ReviewChangeRequestApplyException $exception) {
            return $this->errorResponse(
                $request,
                $this->formatApplyFailureMessage($exception->errors()),
                422
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Không thể duyệt phiếu do hệ thống gặp lỗi không mong đợi. Vui lòng thử lại hoặc liên hệ quản trị viên.',
                500
            );
        }
    }

    private function applyItemsByMode(ChangeRequest $changeRequest, string $mode): array
    {
        $items = $changeRequest->changeRequestItems;

        if ($items->isEmpty()) {
            $errors = $this->validateLegacyChangeRequest($changeRequest);
            if ($errors !== []) {
                throw new ReviewChangeRequestApplyException($errors);
            }

            $applied = $this->applyNewPayloadToSlot($changeRequest);
            if (! $applied) {
                throw new ReviewChangeRequestApplyException([
                    'Legacy slot payload could not be applied.',
                ]);
            }

            return [
                'mode' => $mode,
                'attempted' => 1,
                'applied' => 1,
                'failed' => 0,
                'errors' => [],
            ];
        }

        return $this->applyAllOrNone($changeRequest);
    }

    private function applyAllOrNone(ChangeRequest $changeRequest): array
    {
        $items = $changeRequest->changeRequestItems;
        $now = now();
        $this->lockRequestSlots($changeRequest);

        $errors = $this->validateItemsBeforeApply($changeRequest);
        if ($errors !== []) {
            throw new ReviewChangeRequestApplyException($errors);
        }

        $teacherAssignmentMap = [];
        $classAssignmentMap = [];
        $processedActiveGroups = [];
        $batchSlotIds = $items
            ->pluck('schedule_slot_id')
            ->filter(static fn($id) => is_numeric($id))
            ->map(static fn($id) => (int) $id)
            ->values()
            ->all();

        foreach ($items as $index => $item) {
            $payload = is_array($item->new_payload) ? $item->new_payload : null;
            $slot = $item->scheduleSlot;
            $isSplitFromMergedGroupAction = $this->isSplitFromMergedGroupAction($payload);
            $activeGroupId = ($slot && ! $isSplitFromMergedGroupAction)
                ? $this->getActiveScheduleSlotGroupId($slot)
                : null;

            if ($activeGroupId !== null && isset($processedActiveGroups[$activeGroupId])) {
                continue;
            }

            $error = $this->validateClassScheduleCollision($slot, $payload, $classAssignmentMap, $batchSlotIds);
            if ($error !== null) {
                throw new ReviewChangeRequestApplyException([
                    $this->formatItemError($index, $item->schedule_slot_id, $error),
                ]);
            }

            $error = $this->validateTeacherAssignment($slot, $payload, $teacherAssignmentMap, $batchSlotIds);
            if ($error !== null) {
                throw new ReviewChangeRequestApplyException([
                    $this->formatItemError($index, $item->schedule_slot_id, $error),
                ]);
            }

            if ($activeGroupId !== null) {
                $processedActiveGroups[$activeGroupId] = true;
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
            $errors = [];
            foreach ($pendingItems as $index => $item) {
                $errors[] = $this->formatItemError(
                    $index,
                    $item->schedule_slot_id,
                    'Payload could not be applied due to unresolved collisions or stale slot state.'
                );
            }

            throw new ReviewChangeRequestApplyException($errors);
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

    private function applyNewPayloadToSlot(ChangeRequest $changeRequest): bool
    {
        return $this->applyPayloadToSlot(
            $changeRequest,
            $changeRequest->scheduleSlot,
            is_array($changeRequest->new_payload) ? $changeRequest->new_payload : null,
            []
        );
    }

    private function lockRequestSlots(ChangeRequest $changeRequest): void
    {
        $slotIds = $changeRequest->changeRequestItems
            ->pluck('schedule_slot_id')
            ->filter(static fn($id) => is_numeric($id))
            ->map(static fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($slotIds->isEmpty()) {
            return;
        }

        $lockedSlots = ScheduleSlot::query()
            ->with('teachingSupportRequestItem.request')
            ->whereIn('id', $slotIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($changeRequest->changeRequestItems as $item) {
            $slotId = (int) ($item->schedule_slot_id ?? 0);
            $item->setRelation('scheduleSlot', $slotId > 0 ? $lockedSlots->get($slotId) : null);
        }
    }

    /**
     * @return array<int, string>
     */
    private function validateItemsBeforeApply(ChangeRequest $changeRequest): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->validateActiveMergeGroups($changeRequest));

        foreach ($changeRequest->changeRequestItems as $index => $item) {
            $slot = $item->scheduleSlot;
            $payload = is_array($item->new_payload) ? $item->new_payload : null;

            foreach ($this->validateSlotPayload($slot, $payload, (string) ($changeRequest->change_type ?? 'general')) as $error) {
                $errors[] = $this->formatItemError($index, $item->schedule_slot_id, $error);
            }

            if ($slot === null) {
                continue;
            }

            if ($this->isSlotLocked($slot)) {
                $errors[] = $this->formatItemError(
                    $index,
                    $item->schedule_slot_id,
                    'Slot is locked by teaching support workflow.'
                );
            }

            $staleFields = $this->staleOldPayloadFields($slot, is_array($item->old_payload) ? $item->old_payload : null);
            if ($staleFields !== []) {
                $errors[] = $this->formatItemError(
                    $index,
                    $item->schedule_slot_id,
                    'old_payload is stale for field(s): ' . implode(', ', $staleFields) . '.'
                );
            }
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function validateActiveMergeGroups(ChangeRequest $changeRequest): array
    {
        $items = $changeRequest->changeRequestItems
            ->filter(fn ($item) => $item->scheduleSlot instanceof ScheduleSlot)
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $monthlySchedule = MonthlySchedule::query()->find($changeRequest->monthly_schedule_id);
        if (! $monthlySchedule) {
            return [];
        }

        $allowedMonthlyScheduleIds = MonthlySchedule::query()
            ->where('month', (int) $monthlySchedule->month)
            ->where('year', (int) $monthlySchedule->year)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($allowedMonthlyScheduleIds === []) {
            return [];
        }

        $groupedItems = $items->groupBy(function ($item): string {
            $groupId = $this->getActiveScheduleSlotGroupId($item->scheduleSlot);

            return $groupId !== null ? (string) $groupId : 'slot:' . (int) $item->schedule_slot_id;
        });

        $errors = [];

        foreach ($groupedItems as $groupKey => $groupItems) {
            if (! is_numeric($groupKey)) {
                continue;
            }

            $groupId = (int) $groupKey;
            $groupItems = $groupItems->values();
            if ($groupItems->every(fn ($item): bool => $this->isSplitFromMergedGroupAction(is_array($item->new_payload) ? $item->new_payload : null))) {
                continue;
            }

            $groupSlots = ScheduleSlot::query()
                ->with('scheduleSlotGroup')
                ->where('slot_type', 'subject')
                ->where('assignment_source', 'internal')
                ->whereIn('monthly_schedule_id', $allowedMonthlyScheduleIds)
                ->where('schedule_slot_group_id', $groupId)
                ->get();

            if ($groupSlots->count() !== $groupItems->count()) {
                foreach ($groupItems as $index => $item) {
                    $errors[] = $this->formatItemError(
                        (int) $index,
                        $item->schedule_slot_id,
                        'Active merged group must be included completely before review/apply.'
                    );
                }
                continue;
            }

            $signatures = $groupItems
                ->map(fn ($item): string => $this->payloadSignature(is_array($item->new_payload) ? $item->new_payload : null))
                ->unique();

            if ($signatures->count() <= 1) {
                continue;
            }

            foreach ($groupItems as $index => $item) {
                $errors[] = $this->formatItemError(
                    (int) $index,
                    $item->schedule_slot_id,
                    'All slots in the same active merged group must have the same requested payload.'
                );
            }
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function validateLegacyChangeRequest(ChangeRequest $changeRequest): array
    {
        $changeRequest->setRelation(
            'scheduleSlot',
            $changeRequest->scheduleSlot()
                ->with('teachingSupportRequestItem.request')
                ->lockForUpdate()
                ->first()
        );

        $slot = $changeRequest->scheduleSlot;
        $payload = is_array($changeRequest->new_payload) ? $changeRequest->new_payload : null;
        $errors = $this->validateSlotPayload($slot, $payload, (string) ($changeRequest->change_type ?? 'general'));

        if ($slot !== null && $this->isSlotLocked($slot)) {
            $errors[] = 'Slot is locked by teaching support workflow.';
        }

        $staleFields = $this->staleOldPayloadFields($slot, is_array($changeRequest->old_payload) ? $changeRequest->old_payload : null);
        if ($staleFields !== []) {
            $errors[] = 'old_payload is stale for field(s): ' . implode(', ', $staleFields) . '.';
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function validateSlotPayload(?ScheduleSlot $slot, ?array $payload, string $changeType): array
    {
        if ($slot === null) {
            return ['Target slot is missing.'];
        }

        if ($payload === null || array_is_list($payload)) {
            return ['new_payload must be a valid JSON object.'];
        }

        if ($payload === []) {
            return ['new_payload must not be empty.'];
        }

        $allowedPayload = $this->getAllowedSlotPayload($payload, $changeType);
        $unknownKeys = array_values(array_diff(array_keys($payload), array_keys($allowedPayload)));
        $errors = [];

        if ($unknownKeys !== []) {
            $errors[] = 'new_payload contains unsupported field(s): ' . implode(', ', $unknownKeys) . '.';
        }

        if ($allowedPayload === []) {
            $errors[] = 'new_payload does not contain any applicable slot field.';
        }

        if (array_key_exists('action', $payload)) {
            if (! $this->isSplitFromMergedGroupAction($payload)) {
                $errors[] = 'action is invalid.';
            } elseif ($this->getActiveScheduleSlotGroupId($slot) === null) {
                $errors[] = 'split_from_merged_group action requires an active merged group.';
            }
        }

        if (array_key_exists('assignment_type', $payload)) {
            $assignmentType = $payload['assignment_type'];
            if (! in_array($assignmentType, [null, '', false], true)
                && $this->normalizeAssignmentType($assignmentType) === null) {
                $errors[] = 'assignment_type is invalid.';
            }
        }

        if (array_key_exists('teacher_id', $payload)
            && ! in_array($payload['teacher_id'], [null, '', false], true)
            && ! is_numeric($payload['teacher_id'])) {
            $errors[] = 'teacher_id must be an integer or null.';
        }

        if (array_key_exists('period_number', $payload)
            && ! in_array($payload['period_number'], [null, '', false], true)
            && ! is_numeric($payload['period_number'])) {
            $errors[] = 'period_number must be an integer or null.';
        }

        if (array_key_exists('date', $payload) && $payload['date'] !== null && $payload['date'] !== ''
            && $this->normalizeDateString($payload['date']) === null) {
            $errors[] = 'date is invalid.';
        }

        $effectiveAssignmentType = $this->resolveEffectiveAssignmentType($slot, $allowedPayload);
        if ($effectiveAssignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
            if (array_key_exists('teacher_id', $payload) && ! in_array($payload['teacher_id'], [null, '', false], true)) {
                $errors[] = 'Self-study slot cannot assign a teacher.';
            }

            if (array_key_exists('subject_lesson_id', $payload) && ! in_array($payload['subject_lesson_id'], [null, '', false], true)) {
                $errors[] = 'Self-study slot cannot assign a subject lesson.';
            }
        }

        return $errors;
    }

    private function isSlotLocked(ScheduleSlot $slot): bool
    {
        if ((string) ($slot->assignment_source ?? 'internal') === 'department_support') {
            return true;
        }

        $requestStatus = $slot->teachingSupportRequestItem?->request?->status;

        return in_array($requestStatus, [
            TeachingSupportRequest::STATUS_PENDING_PDT,
            TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
            TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
            TeachingSupportRequest::STATUS_COMPLETED,
        ], true);
    }

    /**
     * @return array<int, string>
     */
    private function staleOldPayloadFields(?ScheduleSlot $slot, ?array $oldPayload): array
    {
        if ($slot === null || $oldPayload === null || $oldPayload === []) {
            return [];
        }

        $currentPayload = $this->extractSlotPayload($slot);
        $staleFields = [];

        foreach ($oldPayload as $field => $expectedValue) {
            if (! array_key_exists($field, $currentPayload)) {
                continue;
            }

            if (! $this->payloadValuesMatch($currentPayload[$field], $expectedValue)) {
                $staleFields[] = (string) $field;
            }
        }

        return $staleFields;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSlotPayload(ScheduleSlot $slot): array
    {
        return [
            'class_id' => $slot->class_id,
            'teacher_id' => $slot->teacher_id,
            'assignment_type' => $slot->assignment_type,
            'subject_id' => $slot->subject_id,
            'subject_lesson_id' => $slot->subject_lesson_id,
            'room_id' => $slot->room_id,
            'date' => $slot->date?->format('Y-m-d'),
            'day_of_week' => $slot->day_of_week,
            'period' => $slot->period,
            'period_number' => $slot->period_number,
            'subject' => $slot->subject,
            'content' => $slot->content,
            'slot_status' => $slot->slot_status,
            'actual_content' => $slot->actual_content,
            'note' => $slot->note,
        ];
    }

    private function payloadValuesMatch(mixed $currentValue, mixed $expectedValue): bool
    {
        if ($currentValue instanceof \DateTimeInterface) {
            $currentValue = $currentValue->format('Y-m-d');
        }

        if ($expectedValue instanceof \DateTimeInterface) {
            $expectedValue = $expectedValue->format('Y-m-d');
        }

        if (is_numeric($currentValue) && is_numeric($expectedValue)) {
            return (string) (int) $currentValue === (string) (int) $expectedValue;
        }

        return $currentValue === $expectedValue;
    }

    private function formatItemError(int $index, mixed $slotId, string $message): string
    {
        $slotLabel = is_numeric($slotId) ? 'slot #' . (int) $slotId : 'missing slot';

        return 'Item #' . ($index + 1) . ' (' . $slotLabel . '): ' . $message;
    }

    private function getActiveScheduleSlotGroupId(ScheduleSlot $slot): ?int
    {
        if ($slot->scheduleSlotGroup?->status !== 'active') {
            return null;
        }

        return is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null;
    }

    private function payloadSignature(?array $payload): string
    {
        if ($payload === null) {
            return '';
        }

        ksort($payload);

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
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
        $isSplitFromMergedGroupAction = $this->isSplitFromMergedGroupAction($filteredPayload);
        $effectiveAssignmentType = $this->resolveEffectiveAssignmentType($slot, $filteredPayload);

        if ($effectiveAssignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
            $filteredPayload['assignment_type'] = ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY;
            $filteredPayload['teacher_id'] = null;
            $filteredPayload['subject_lesson_id'] = null;
            $filteredPayload['schedule_slot_group_id'] = null;
        }

        if ($filteredPayload === []) {
            return false;
        }

        $candidateDate = $this->resolveEffectiveDate($slot, $filteredPayload);
        $candidatePeriodNumber = $this->resolveEffectivePeriodNumber($slot, $filteredPayload);

        if ($candidateDate === null || $candidatePeriodNumber === null) {
            return false;
        }

        if ($effectiveAssignmentType !== ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
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

        try {
            if ($effectiveAssignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
                if ($isSplitFromMergedGroupAction) {
                    $this->splitSlotFromScheduleSlotGroup($slot);
                } else {
                    $this->splitScheduleSlotGroupForSelfStudy($slot);
                }
            } elseif ($isSplitFromMergedGroupAction) {
                $this->splitSlotFromScheduleSlotGroup($slot);
            }

            unset($filteredPayload['action']);
            $slot->fill($filteredPayload);

            if ($slot->isDirty()) {
                $slot->save();
            }
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    private function splitScheduleSlotGroupForSelfStudy(ScheduleSlot $slot): void
    {
        $groupId = is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : 0;
        if ($groupId <= 0) {
            return;
        }

        ScheduleSlot::query()
            ->where('schedule_slot_group_id', $groupId)
            ->update([
                'schedule_slot_group_id' => null,
                'updated_at' => now(),
            ]);

        ScheduleSlotGroup::query()
            ->whereKey($groupId)
            ->where('status', 'active')
            ->update([
                'status' => 'inactive',
                'updated_at' => now(),
            ]);

        $slot->schedule_slot_group_id = null;
        $slot->unsetRelation('scheduleSlotGroup');
    }

    private function splitSlotFromScheduleSlotGroup(ScheduleSlot $slot): void
    {
        $groupId = is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : 0;
        if ($groupId <= 0) {
            return;
        }

        ScheduleSlot::query()
            ->whereKey($slot->id)
            ->update([
                'schedule_slot_group_id' => null,
                'updated_at' => now(),
            ]);

        $slot->schedule_slot_group_id = null;
        $slot->unsetRelation('scheduleSlotGroup');

        $remainingSlotIds = ScheduleSlot::query()
            ->where('schedule_slot_group_id', $groupId)
            ->pluck('id');

        if ($remainingSlotIds->count() >= 2) {
            return;
        }

        if ($remainingSlotIds->isNotEmpty()) {
            ScheduleSlot::query()
                ->whereIn('id', $remainingSlotIds->all())
                ->update([
                    'schedule_slot_group_id' => null,
                    'updated_at' => now(),
                ]);
        }

        ScheduleSlotGroup::query()
            ->whereKey($groupId)
            ->where('status', 'active')
            ->update([
                'status' => 'inactive',
                'updated_at' => now(),
            ]);
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

        $effectiveAssignmentType = $this->resolveEffectiveAssignmentType($slot, $payload);
        if ($effectiveAssignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
            return null;
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
            'action',
            'teacher_id',
            'assignment_type',
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

    private function isSplitFromMergedGroupAction(?array $payload): bool
    {
        return ($payload['action'] ?? null) === self::PAYLOAD_ACTION_SPLIT_FROM_MERGED_GROUP;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function resolveEffectiveAssignmentType(ScheduleSlot $slot, ?array $payload): ?string
    {
        if (is_array($payload) && array_key_exists('assignment_type', $payload)) {
            return $this->normalizeAssignmentType($payload['assignment_type']);
        }

        return $this->normalizeAssignmentType($slot->assignment_type);
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

    private function normalizeAssignmentType(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return $value === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY ? $value : null;
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

    /**
     * @param array<int, string> $errors
     */
    private function formatApplyFailureMessage(array $errors): string
    {
        $parsedItems = collect($errors)
            ->map(function (string $error): ?array {
                if (! preg_match('/^Item #(?<index>\d+) \(slot #(?<slot>\d+)\): (?<message>.+)$/u', $error, $matches)) {
                    return null;
                }

                return [
                    'index' => (int) $matches['index'],
                    'slot' => (int) $matches['slot'],
                    'message' => $this->friendlyApplyFailureMessage(trim($matches['message'])),
                ];
            })
            ->filter()
            ->values();

        if ($parsedItems->isEmpty()) {
            return 'Không thể duyệt phiếu vì có dữ liệu chưa hợp lệ. Vui lòng kiểm tra lại các tiết trong phiếu.';
        }

        $groupedBySlot = $parsedItems
            ->groupBy('slot')
            ->map(fn ($items) => $items->pluck('message')->unique()->values()->all());

        $parts = [];
        foreach ($groupedBySlot->take(3) as $slotId => $messages) {
            $parts[] = 'Tiết #' . $slotId . ': ' . implode('; ', array_slice($messages, 0, 2));
        }

        $remainingCount = max(0, $groupedBySlot->count() - count($parts));
        $message = 'Không thể duyệt phiếu vì một số tiết chưa hợp lệ: ' . implode(' | ', $parts) . '.';

        if ($remainingCount > 0) {
            $message .= ' Còn ' . $remainingCount . ' tiết khác cần kiểm tra.';
        }

        return $message;
    }

    private function friendlyApplyFailureMessage(string $message): string
    {
        $normalized = strtolower($message);

        return match (true) {
            str_contains($normalized, 'self-study slot cannot assign a teacher') => 'Tiết tự nghiên cứu không được gán giảng viên.',
            str_contains($normalized, 'self-study slot cannot assign a subject lesson') => 'Tiết tự nghiên cứu không được chọn bài học.',
            str_contains($normalized, 'old_payload is stale') => 'Dữ liệu cũ của tiết đã thay đổi, vui lòng tải lại phiếu.',
            str_contains($normalized, 'slot is locked by teaching support workflow') => 'Tiết đang bị khóa bởi luồng hỗ trợ liên khoa.',
            str_contains($normalized, 'target slot is missing') => 'Tiết gốc không còn tồn tại.',
            str_contains($normalized, 'new_payload must be a valid json object') => 'Dữ liệu thay đổi của tiết không hợp lệ.',
            str_contains($normalized, 'payload could not be applied due to unresolved collisions or stale slot state') => 'Tiết bị xung đột lịch hoặc đã đổi trạng thái, vui lòng kiểm tra lại.',
            default => $message,
        };
    }
}

final class ReviewChangeRequestApplyException extends \RuntimeException
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct(implode("\n", $errors));
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
