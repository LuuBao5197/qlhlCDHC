<?php

namespace Modules\Schedule\Application\Shared;

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\TeachingSupportAuditLog;
use Modules\Schedule\Models\TeachingSupportChangeRequest;
use Modules\Schedule\Models\TeachingSupportChangeRequestItem;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Schedule\Models\TeachingSupportRequestItem;

class TeachingSupportAuditService
{
    /**
     * @param array{
     *     request_id?:int|null,
     *     request_item_id?:int|null,
     *     schedule_slot_id?:int|null,
     *     change_request_id?:int|null,
     *     action:string,
     *     actor?:User|null,
     *     old_values?:array<string, mixed>|null,
     *     new_values?:array<string, mixed>|null,
     *     note?:string|null,
     *     occurred_at?:Carbon|null
     * } $payload
     */
    public function record(array $payload): TeachingSupportAuditLog
    {
        $actor = $payload['actor'] ?? null;
        $occurredAt = $payload['occurred_at'] ?? now();

        return TeachingSupportAuditLog::query()->create([
            'request_id' => $payload['request_id'] ?? null,
            'request_item_id' => $payload['request_item_id'] ?? null,
            'schedule_slot_id' => $payload['schedule_slot_id'] ?? null,
            'change_request_id' => $payload['change_request_id'] ?? null,
            'action' => (string) $payload['action'],
            'actor_user_id' => $actor instanceof User ? $actor->id : null,
            'actor_name_snapshot' => $actor instanceof User ? (string) ($actor->name ?? $actor->email ?? '-') : null,
            'actor_role_snapshot' => $actor instanceof User ? (string) ($actor->role ?? '-') : null,
            'actor_department_id' => $actor instanceof User ? $actor->department_id : null,
            'actor_department_name_snapshot' => $actor instanceof User ? $actor->department?->name : null,
            'old_values' => $payload['old_values'] ?? null,
            'new_values' => $payload['new_values'] ?? null,
            'note' => $payload['note'] ?? null,
            'occurred_at' => $occurredAt,
        ]);
    }

    public function snapshotSupportRequest(TeachingSupportRequest $request): array
    {
        return [
            'id' => (int) $request->id,
            'status' => $request->status,
            'requesting_department_id' => $request->requesting_department_id,
            'requesting_department_name' => $request->requestingDepartment?->name,
            'proposed_supporting_department_id' => $request->proposed_supporting_department_id,
            'proposed_supporting_department_name' => $request->proposedSupportingDepartment?->name,
            'assigned_supporting_department_id' => $request->assigned_supporting_department_id,
            'assigned_supporting_department_name' => $request->assignedSupportingDepartment?->name,
            'request_note' => $request->request_note,
            'pdt_note' => $request->pdt_note,
            'submitted_at' => $request->submitted_at?->toIso8601String(),
            'pdt_processed_at' => $request->pdt_processed_at?->toIso8601String(),
        ];
    }

    public function snapshotRequestItem(TeachingSupportRequestItem $item): array
    {
        $slot = $item->scheduleSlot;

        return [
            'id' => (int) $item->id,
            'request_id' => (int) $item->request_id,
            'schedule_slot_id' => (int) $item->schedule_slot_id,
            'status' => $item->status,
            'assigned_teacher_id' => $item->assigned_teacher_id,
            'assigned_teacher_name' => $item->assignedTeacher?->name,
            'assigned_by' => $item->assigned_by,
            'assigned_at' => $item->assigned_at?->toIso8601String(),
            'note' => $item->note,
            'slot' => $this->snapshotSlot($slot),
        ];
    }

    public function snapshotChangeRequest(TeachingSupportChangeRequest $changeRequest): array
    {
        return [
            'id' => (int) $changeRequest->id,
            'teaching_support_request_id' => (int) $changeRequest->teaching_support_request_id,
            'status' => $changeRequest->status,
            'reason' => $changeRequest->reason,
            'revision_no' => (int) $changeRequest->revision_no,
            'requesting_department_id' => $changeRequest->requesting_department_id,
            'requesting_department_name' => $changeRequest->requestingDepartment?->name,
            'proposed_supporting_department_id' => $changeRequest->proposed_supporting_department_id,
            'proposed_supporting_department_name' => $changeRequest->proposedSupportingDepartment?->name,
            'assigned_supporting_department_id' => $changeRequest->assigned_supporting_department_id,
            'assigned_supporting_department_name' => $changeRequest->assignedSupportingDepartment?->name,
            'submitted_at' => $changeRequest->submitted_at?->toIso8601String(),
            'pdt_processed_at' => $changeRequest->pdt_processed_at?->toIso8601String(),
        ];
    }

    public function snapshotChangeItem(TeachingSupportChangeRequestItem $item): array
    {
        return [
            'id' => (int) $item->id,
            'change_request_id' => (int) $item->change_request_id,
            'support_request_item_id' => $item->support_request_item_id,
            'schedule_slot_id' => (int) $item->schedule_slot_id,
            'action' => $item->action,
            'status' => $item->status,
            'previous_teacher_id' => $item->previous_teacher_id,
            'note' => $item->note,
            'old_snapshot' => $item->old_snapshot,
            'proposed_snapshot' => $item->proposed_snapshot,
            'slot' => $this->snapshotSlot($item->scheduleSlot),
        ];
    }

    public function snapshotSlot(?ScheduleSlot $slot): array|null
    {
        if (! $slot instanceof ScheduleSlot) {
            return null;
        }

        return [
            'id' => (int) $slot->id,
            'monthly_schedule_id' => $slot->monthly_schedule_id,
            'schedule_slot_group_id' => $slot->schedule_slot_group_id,
            'class_id' => $slot->class_id,
            'class_code' => $slot->trainingClass?->code,
            'class_name' => $slot->trainingClass?->name,
            'teacher_id' => $slot->teacher_id,
            'teacher_name' => $slot->teacher?->name,
            'teacher_code' => $slot->teacher?->teacher_code,
            'assignment_source' => $slot->assignment_source,
            'teaching_support_request_item_id' => $slot->teaching_support_request_item_id,
            'subject_id' => $slot->subject_id,
            'subject_code' => $slot->subjectModel?->code,
            'subject_name' => $slot->subjectModel?->name,
            'subject_lesson_id' => $slot->subject_lesson_id,
            'subject_lesson_title' => $slot->subjectLesson?->title,
            'room_id' => $slot->room_id,
            'room_code' => $slot->room?->code,
            'room_name' => $slot->room?->name,
            'slot_type' => $slot->slot_type,
            'date' => $slot->date?->format('Y-m-d'),
            'day_of_week' => $slot->day_of_week,
            'period_number' => $slot->period_number,
            'period' => $slot->period,
            'subject' => $slot->subject,
            'content' => $slot->content,
            'note' => $slot->note,
            'slot_status' => $slot->slot_status,
        ];
    }
}
