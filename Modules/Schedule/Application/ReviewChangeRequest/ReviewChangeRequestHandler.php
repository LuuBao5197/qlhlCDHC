<?php

namespace Modules\Schedule\Application\ReviewChangeRequest;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Modules\Training\Models\ChangeRequest;
use Throwable;

class ReviewChangeRequestHandler
{
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
        $assignmentMap = [];
        $batchSlotIds = $items
            ->pluck('schedule_slot_id')
            ->filter(static fn($id) => is_numeric($id))
            ->map(static fn($id) => (int) $id)
            ->values()
            ->all();

        foreach ($items as $index => $item) {
            $payload = is_array($item->new_payload) ? $item->new_payload : null;
            $error = $this->validateTeacherAssignment($item->scheduleSlot, $payload, $assignmentMap, $batchSlotIds);
            if ($error !== null) {
                throw new \RuntimeException(
                    'Failed to validate item #' . ($index + 1) . ' in all_or_none mode. ' . $error
                );
            }
        }

        foreach ($items as $index => $item) {
            $isApplied = $this->applyPayloadToSlot(
                $item->scheduleSlot,
                is_array($item->new_payload) ? $item->new_payload : null
            );

            if (! $isApplied) {
                throw new \RuntimeException(
                    'Failed to apply item #' . ($index + 1) . ' in all_or_none mode.'
                );
            }
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
        $assignmentMap = [];
        $batchSlotIds = $items
            ->pluck('schedule_slot_id')
            ->filter(static fn($id) => is_numeric($id))
            ->map(static fn($id) => (int) $id)
            ->values()
            ->all();

        foreach ($items as $index => $item) {
            $payload = is_array($item->new_payload) ? $item->new_payload : null;
            $error = $this->validateTeacherAssignment($item->scheduleSlot, $payload, $assignmentMap, $batchSlotIds);
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

            $isApplied = $this->applyPayloadToSlot(
                $item->scheduleSlot,
                $payload
            );

            if ($isApplied) {
                $applied++;
                $item->update([
                    'apply_status' => 'applied',
                    'apply_error' => null,
                    'applied_at' => $now,
                ]);
                continue;
            }

            $failed++;
            $errorMessage = 'Failed to apply item #' . ($index + 1) . ' in best_effort mode.';
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
            $changeRequest->scheduleSlot,
            is_array($changeRequest->new_payload) ? $changeRequest->new_payload : null
        );
    }

    private function applyPayloadToSlot(?ScheduleSlot $slot, ?array $payload): bool
    {
        if ($slot === null || $payload === null) {
            return false;
        }

        $filteredPayload = $this->getAllowedSlotPayload($payload);

        if ($filteredPayload === []) {
            return false;
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

        $candidateTeacherId = array_key_exists('teacher_id', $payload ?? [])
            ? $this->normalizeNullableNumber($payload['teacher_id'])
            : $this->normalizeNullableNumber($slot->teacher_id);

        if ($candidateTeacherId === null) {
            return null;
        }

        $date = $slot->date?->format('Y-m-d');
        $periodNumber = (int) $slot->period_number;
        if ($date === null || $date === '') {
            return 'Slot date is missing.';
        }

        $assignmentKey = $candidateTeacherId . '|' . $date . '|' . $periodNumber;
        if (isset($assignmentMap[$assignmentKey]) && $assignmentMap[$assignmentKey] !== (int) $slot->id) {
            return 'Teacher conflict within selected request items.';
        }

        $assignmentMap[$assignmentKey] = (int) $slot->id;

        $hasExistingConflict = ScheduleSlot::query()
            ->where('teacher_id', $candidateTeacherId)
            ->whereDate('date', $date)
            ->where('period_number', $periodNumber)
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
    private function getAllowedSlotPayload(array $payload): array
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

        return array_intersect_key($payload, array_flip($allowedKeys));
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
