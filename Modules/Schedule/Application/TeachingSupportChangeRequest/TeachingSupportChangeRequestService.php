<?php

namespace Modules\Schedule\Application\TeachingSupportChangeRequest;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\Shared\TeachingSupportAuditService;
use Modules\Schedule\Application\Shared\TeacherAvailabilityService;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\TeachingSupportChangeRequest;
use Modules\Schedule\Models\TeachingSupportChangeRequestItem;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Schedule\Models\TeachingSupportRequestItem;

class TeachingSupportChangeRequestService
{
    private const ACTIVE_CHANGE_STATUSES = [
        TeachingSupportChangeRequest::STATUS_PENDING_PDT,
    ];

    public function __construct(
        private TeacherAvailabilityService $teacherAvailabilityService,
        private TeachingSupportAuditService $auditService
    ) {}

    public function canCreateChangeRequest(TeachingSupportRequest $request, User $actor): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return $actor->isDepartmentStaff()
            && (int) $request->requesting_department_id === (int) $actor->department_id
            && in_array($request->status, [
                TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                TeachingSupportRequest::STATUS_COMPLETED,
            ], true);
    }

    public function canWithdrawRequest(TeachingSupportRequest $request, User $actor): bool
    {
        if ($actor->isAdmin() || $actor->isTrainingOffice()) {
            return false;
        }

        return $actor->isDepartmentStaff()
            && (int) $request->requesting_department_id === (int) $actor->department_id
            && $request->status === TeachingSupportRequest::STATUS_PENDING_PDT;
    }

    public function buildDetailPayload(TeachingSupportRequest $request, ?User $actor = null): array
    {
        $changeRequests = TeachingSupportChangeRequest::query()
            ->with([
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.room',
                'items.scheduleSlot.teacher',
                'items.supportRequestItem.assignedTeacher',
                'items.previousTeacher',
                'auditLogs.actor',
                'auditLogs.actorDepartment',
                'auditLogs.requestItem',
                'auditLogs.scheduleSlot',
            ])
            ->where('teaching_support_request_id', $request->id)
            ->orderByDesc('revision_no')
            ->get();

        $historyLogs = $request->auditLogs()
            ->with(['actor', 'actorDepartment', 'changeRequest', 'requestItem', 'scheduleSlot'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        return [
            'change_requests' => $changeRequests,
            'history_logs' => $historyLogs,
            'can_create' => $actor ? $this->canCreateChangeRequest($request, $actor) : false,
        ];
    }

    public function buildCreatePayload(TeachingSupportRequest $request, ?User $actor = null): array
    {
        $currentItems = $request->items()
            ->with([
                'scheduleSlot.monthlySchedule.plan',
                'scheduleSlot.trainingClass',
                'scheduleSlot.subjectModel.department',
                'scheduleSlot.subjectLesson',
                'scheduleSlot.room',
                'scheduleSlot.teacher',
                'assignedTeacher',
            ])
            ->get();

        $monthlyScheduleIds = $currentItems
            ->pluck('scheduleSlot.monthly_schedule_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $availableSlots = ScheduleSlot::query()
            ->with([
                'monthlySchedule.plan',
                'trainingClass',
                'teacher',
                'subjectModel.department',
                'subjectLesson',
                'room',
                'scheduleSlotGroup',
            ])
            ->whereIn('monthly_schedule_id', $monthlyScheduleIds)
            ->where('slot_type', 'subject')
            ->whereNull('teacher_id')
            ->where('assignment_source', 'internal')
            ->whereHas('subjectModel', function ($query) use ($request): void {
                $query->where('department_id', $request->requesting_department_id);
            })
            ->whereNotIn('id', $currentItems->pluck('schedule_slot_id')->all())
            ->orderBy('date')
            ->orderBy('period_number')
            ->orderBy('id')
            ->get();

        return [
            'current_items' => $currentItems,
            'available_slots' => $availableSlots,
            'can_create' => $actor ? $this->canCreateChangeRequest($request, $actor) : false,
            'request_snapshot' => $this->auditService->snapshotSupportRequest($request),
        ];
    }

    /**
     * @param array{reason:string} $payload
     */
    public function createChangeRequest(TeachingSupportRequest $request, User $actor, array $payload): TeachingSupportChangeRequest
    {
        return DB::transaction(function () use ($request, $actor, $payload): TeachingSupportChangeRequest {
            $lockedRequest = TeachingSupportRequest::query()
                ->with([
                    'requestingDepartment',
                    'proposedSupportingDepartment',
                    'assignedSupportingDepartment',
                    'items.scheduleSlot.trainingClass',
                    'items.scheduleSlot.subjectModel.department',
                    'items.scheduleSlot.subjectLesson',
                    'items.scheduleSlot.room',
                    'items.scheduleSlot.teacher',
                    'items.assignedTeacher',
                ])
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->canCreateChangeRequest($lockedRequest, $actor)) {
                throw ValidationException::withMessages([
                    'scope' => 'Khong co quyen tao phieu huy yeu cau nay.',
                ]);
            }

            if ($this->hasActiveChangeRequest($lockedRequest->id)) {
                throw ValidationException::withMessages([
                    'change_request' => 'Yeu cau nay dang co mot phieu huy active.',
                ]);
            }

            $revisionNo = (int) (TeachingSupportChangeRequest::query()
                ->where('teaching_support_request_id', $lockedRequest->id)
                ->max('revision_no') ?? 0) + 1;

            $changeRequest = TeachingSupportChangeRequest::query()->create([
                'teaching_support_request_id' => $lockedRequest->id,
                'requesting_department_id' => $lockedRequest->requesting_department_id,
                'proposed_supporting_department_id' => $lockedRequest->assigned_supporting_department_id,
                'assigned_supporting_department_id' => $lockedRequest->assigned_supporting_department_id,
                'status' => TeachingSupportChangeRequest::STATUS_PENDING_PDT,
                'reason' => trim((string) ($payload['reason'] ?? '')),
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'pdt_processed_by' => null,
                'pdt_processed_at' => null,
                'pdt_note' => null,
                'revision_no' => $revisionNo,
            ]);

            $now = now();
            $lockedItems = $lockedRequest->items()
                ->with([
                    'scheduleSlot.trainingClass',
                    'scheduleSlot.subjectModel.department',
                    'scheduleSlot.subjectLesson',
                    'scheduleSlot.room',
                    'scheduleSlot.teacher',
                    'assignedTeacher',
                ])
                ->lockForUpdate()
                ->get();

            if ($lockedItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'change_request' => 'Khong co tiet nao de huy.',
                ]);
            }

            foreach ($lockedItems as $item) {
                $slot = $item->scheduleSlot;
                $changeRequest->items()->create([
                    'support_request_item_id' => $item->id,
                    'schedule_slot_id' => $slot?->id,
                    'action' => TeachingSupportChangeRequestItem::ACTION_REMOVE,
                    'old_snapshot' => $this->auditService->snapshotSlot($slot) ?? [],
                    'proposed_snapshot' => [],
                    'previous_teacher_id' => $item->assigned_teacher_id ?? $slot?->teacher_id,
                    'status' => TeachingSupportChangeRequestItem::STATUS_PENDING,
                    'note' => trim((string) ($payload['reason'] ?? '')),
                ]);
            }

            $this->auditService->record([
                'request_id' => $lockedRequest->id,
                'change_request_id' => $changeRequest->id,
                'action' => 'support_cancellation_request_submitted',
                'actor' => $actor,
                'old_values' => null,
                'new_values' => [
                    'change_request' => $this->auditService->snapshotChangeRequest($changeRequest),
                    'items' => $changeRequest->items->map(fn (TeachingSupportChangeRequestItem $item) => $this->auditService->snapshotChangeItem($item))->all(),
                ],
                'note' => (string) ($payload['reason'] ?? ''),
                'occurred_at' => $now,
            ]);

            return $changeRequest->fresh([
                'teachingSupportRequest.requestingDepartment',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.room',
                'items.scheduleSlot.teacher',
                'items.supportRequestItem.assignedTeacher',
                'items.previousTeacher',
                'auditLogs.actor',
                'auditLogs.actorDepartment',
                'auditLogs.requestItem',
                'auditLogs.scheduleSlot',
            ]);
        });
    }

    public function reviewChangeRequest(
        TeachingSupportChangeRequest $changeRequest,
        User $actor,
        string $action,
        ?string $pdtNote
    ): TeachingSupportChangeRequest {
        return DB::transaction(function () use ($changeRequest, $actor, $action, $pdtNote): TeachingSupportChangeRequest {
            $lockedChangeRequest = TeachingSupportChangeRequest::query()
                ->with([
                    'teachingSupportRequest.requestingDepartment',
                    'teachingSupportRequest.proposedSupportingDepartment',
                    'teachingSupportRequest.assignedSupportingDepartment',
                    'teachingSupportRequest.items.scheduleSlot.trainingClass',
                    'teachingSupportRequest.items.scheduleSlot.subjectModel.department',
                    'teachingSupportRequest.items.scheduleSlot.subjectLesson',
                    'teachingSupportRequest.items.scheduleSlot.room',
                    'teachingSupportRequest.items.scheduleSlot.teacher',
                    'requestingDepartment',
                    'proposedSupportingDepartment',
                    'assignedSupportingDepartment',
                    'items.scheduleSlot.trainingClass',
                    'items.scheduleSlot.subjectModel.department',
                    'items.scheduleSlot.subjectLesson',
                    'items.scheduleSlot.room',
                    'items.scheduleSlot.teacher',
                    'items.supportRequestItem.assignedTeacher',
                    'items.previousTeacher',
                ])
                ->whereKey($changeRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedChangeRequest->status !== TeachingSupportChangeRequest::STATUS_PENDING_PDT) {
                throw ValidationException::withMessages([
                    'change_request' => 'Chi xu ly khi phieu huy dang cho PDT.',
                ]);
            }

            $originalRequest = $lockedChangeRequest->teachingSupportRequest()
                ->lockForUpdate()
                ->first();
            if (! $originalRequest instanceof TeachingSupportRequest) {
                throw ValidationException::withMessages([
                    'change_request' => 'Khong tim thay yeu cau goc.',
                ]);
            }

            $oldChangeValues = $this->auditService->snapshotChangeRequest($lockedChangeRequest);

            if ($action === 'return') {
                $lockedChangeRequest->fill([
                    'status' => TeachingSupportChangeRequest::STATUS_RETURNED,
                    'pdt_processed_by' => $actor->id,
                    'pdt_processed_at' => now(),
                    'pdt_note' => $pdtNote,
                ]);
                $lockedChangeRequest->save();

                $lockedChangeRequest->items()->update([
                    'status' => TeachingSupportChangeRequestItem::STATUS_RETURNED,
                    'updated_at' => now(),
                ]);

                $this->auditService->record([
                    'request_id' => $originalRequest->id,
                    'change_request_id' => $lockedChangeRequest->id,
                    'action' => 'support_change_request_returned',
                    'actor' => $actor,
                    'old_values' => $oldChangeValues,
                    'new_values' => $this->auditService->snapshotChangeRequest($lockedChangeRequest),
                    'note' => $pdtNote,
                ]);

                return $lockedChangeRequest->fresh([
                    'teachingSupportRequest',
                    'requestingDepartment',
                    'proposedSupportingDepartment',
                    'assignedSupportingDepartment',
                    'submittedBy',
                    'pdtProcessedBy',
                    'items.scheduleSlot.trainingClass',
                    'items.scheduleSlot.subjectModel.department',
                    'items.scheduleSlot.subjectLesson',
                    'items.scheduleSlot.room',
                    'items.scheduleSlot.teacher',
                    'items.supportRequestItem.assignedTeacher',
                    'items.previousTeacher',
                    'auditLogs.actor',
                    'auditLogs.actorDepartment',
                    'auditLogs.requestItem',
                    'auditLogs.scheduleSlot',
                ]);
            }

            if ($action !== 'approve') {
                throw ValidationException::withMessages([
                    'action' => 'Hanh dong PDT khong hop le.',
                ]);
            }

            $oldRequestValues = $this->auditService->snapshotSupportRequest($originalRequest);

            $now = now();
            $oldChangeValues = $this->auditService->snapshotChangeRequest($lockedChangeRequest);

            foreach ($lockedChangeRequest->items as $item) {
                $this->applyCancellationItem($item, $actor, $now);
            }

            $lockedChangeRequest->fill([
                'status' => TeachingSupportChangeRequest::STATUS_COMPLETED,
                'pdt_processed_by' => $actor->id,
                'pdt_processed_at' => $now,
                'pdt_note' => $pdtNote,
            ]);
            $lockedChangeRequest->save();

            $this->cancelOriginalRequest($originalRequest, $actor, $pdtNote, $lockedChangeRequest);

            $this->auditService->record([
                'request_id' => $originalRequest->id,
                'change_request_id' => $lockedChangeRequest->id,
                'action' => 'support_cancellation_request_approved',
                'actor' => $actor,
                'old_values' => [
                    'change_request' => $oldChangeValues,
                    'request' => $oldRequestValues,
                ],
                'new_values' => [
                    'change_request' => $this->auditService->snapshotChangeRequest($lockedChangeRequest),
                    'request' => $this->auditService->snapshotSupportRequest($originalRequest),
                ],
                'note' => $pdtNote,
                'occurred_at' => $now,
            ]);

            return $lockedChangeRequest->fresh([
                'teachingSupportRequest.requestingDepartment',
                'teachingSupportRequest.assignedSupportingDepartment',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.room',
                'items.scheduleSlot.teacher',
                'items.supportRequestItem.assignedTeacher',
                'items.previousTeacher',
                'auditLogs.actor',
                'auditLogs.actorDepartment',
                'auditLogs.requestItem',
                'auditLogs.scheduleSlot',
            ]);
        });
    }

    private function hasActiveChangeRequest(int $supportRequestId): bool
    {
        return TeachingSupportChangeRequest::query()
            ->where('teaching_support_request_id', $supportRequestId)
            ->where('status', TeachingSupportChangeRequest::STATUS_PENDING_PDT)
            ->exists();
    }

    private function applyCancellationItem(TeachingSupportChangeRequestItem $item, User $actor, \DateTimeInterface $now): void
    {
        $supportItem = $item->supportRequestItem()->lockForUpdate()->first();
        $slot = $item->scheduleSlot()->lockForUpdate()->first();
        $oldItemValues = $this->auditService->snapshotChangeItem($item);
        $oldSlotValues = $this->auditService->snapshotSlot($slot);

        if ($supportItem instanceof TeachingSupportRequestItem) {
            $supportItem->fill([
                'status' => TeachingSupportRequestItem::STATUS_REJECTED,
                'assigned_teacher_id' => null,
                'assigned_by' => $actor->id,
                'assigned_at' => $now,
                'note' => $item->note,
            ]);
            $supportItem->save();
        }

        if ($slot instanceof ScheduleSlot) {
            $linkedToSupport = (string) ($slot->assignment_source ?? 'internal') === 'department_support'
                && (int) ($slot->teaching_support_request_item_id ?? 0) === (int) ($supportItem?->id ?? 0);

            if ($linkedToSupport) {
                $slot->fill([
                    'teacher_id' => null,
                    'assignment_source' => 'internal',
                    'teaching_support_request_item_id' => null,
                ]);
                $slot->save();
            }
        }

        $item->fill([
            'status' => TeachingSupportChangeRequestItem::STATUS_COMPLETED,
        ]);
        $item->save();

        $this->auditService->record([
            'request_id' => $item->changeRequest?->teaching_support_request_id,
            'request_item_id' => $supportItem?->id,
            'schedule_slot_id' => $slot?->id,
            'change_request_id' => $item->change_request_id,
            'action' => 'support_request_item_cancelled',
            'actor' => $actor,
            'old_values' => [
                'item' => $oldItemValues,
                'slot' => $oldSlotValues,
            ],
            'new_values' => [
                'item' => $this->auditService->snapshotChangeItem($item),
                'slot' => $this->auditService->snapshotSlot($slot),
            ],
            'note' => $item->note,
            'occurred_at' => $now,
        ]);
    }

    private function cancelOriginalRequest(TeachingSupportRequest $request, User $actor, ?string $reason, ?TeachingSupportChangeRequest $changeRequest = null): void
    {
        $now = now();
        $request->loadMissing([
            'items.scheduleSlot.trainingClass',
            'items.scheduleSlot.subjectModel.department',
            'items.scheduleSlot.subjectLesson',
            'items.scheduleSlot.room',
            'items.scheduleSlot.teacher',
            'items.assignedTeacher',
        ]);

        $oldRequestValues = $this->auditService->snapshotSupportRequest($request);
        $itemSnapshots = [];
        $slotSnapshots = [];

        foreach ($request->items as $item) {
            $supportItem = $item;
            $slot = $supportItem->scheduleSlot()->lockForUpdate()->first();
            $oldItemSnapshots = $this->auditService->snapshotRequestItem($supportItem);
            $oldSlotSnapshot = $this->auditService->snapshotSlot($slot);

            if ($supportItem->assigned_teacher_id !== null) {
                $supportItem->fill([
                    'status' => TeachingSupportRequestItem::STATUS_REJECTED,
                    'assigned_teacher_id' => null,
                    'assigned_by' => $actor->id,
                    'assigned_at' => $now,
                    'note' => $reason,
                ]);
                $supportItem->save();
            } else {
                $supportItem->fill([
                    'status' => TeachingSupportRequestItem::STATUS_REJECTED,
                    'assigned_by' => $actor->id,
                    'assigned_at' => $now,
                    'note' => $reason,
                ]);
                $supportItem->save();
            }

            if ($slot instanceof ScheduleSlot) {
                $linkedToSupport = (string) ($slot->assignment_source ?? 'internal') === 'department_support'
                    && (int) ($slot->teaching_support_request_item_id ?? 0) === (int) $supportItem->id;

                if ($linkedToSupport) {
                    $slot->fill([
                        'teacher_id' => null,
                        'assignment_source' => 'internal',
                        'teaching_support_request_item_id' => null,
                    ]);
                    $slot->save();
                }
            }

            $item->status = TeachingSupportRequestItem::STATUS_REJECTED;
            $item->save();

            $itemSnapshots[] = [
                'old' => $oldItemSnapshots,
                'new' => $this->auditService->snapshotRequestItem($supportItem),
            ];
            $slotSnapshots[] = [
                'old' => $oldSlotSnapshot,
                'new' => $this->auditService->snapshotSlot($slot),
            ];
        }

        $request->status = TeachingSupportRequest::STATUS_CANCELLED;
        $request->pdt_note = $reason;
        $request->save();

        $this->auditService->record([
            'request_id' => $request->id,
            'change_request_id' => $changeRequest?->id,
            'action' => $changeRequest ? 'support_cancellation_request_applied' : 'support_request_withdrawn',
            'actor' => $actor,
            'old_values' => [
                'request' => $oldRequestValues,
                'items' => $itemSnapshots,
                'slots' => $slotSnapshots,
            ],
            'new_values' => $this->auditService->snapshotSupportRequest($request),
            'note' => $reason,
            'occurred_at' => $now,
        ]);
    }

}
