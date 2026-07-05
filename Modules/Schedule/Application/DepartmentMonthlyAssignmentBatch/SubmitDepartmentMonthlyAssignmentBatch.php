<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;

class SubmitDepartmentMonthlyAssignmentBatch
{
    public function __construct(
        private BuildOrRefreshDraftBatch $buildOrRefreshDraftBatch
    ) {}

    public function handle(MonthlySchedule $anchorMonthlySchedule, User $actor): DepartmentMonthlyAssignmentBatch
    {
        return DB::transaction(function () use ($anchorMonthlySchedule, $actor): DepartmentMonthlyAssignmentBatch {
            $batch = $this->buildOrRefreshDraftBatch->handle($anchorMonthlySchedule, $actor);

            if (! in_array($batch->status, ['draft', 'returned'], true)) {
                throw ValidationException::withMessages([
                    'batch' => 'Chi co the gui batch khi dang o trang thai draft hoac returned.',
                ]);
            }

            $this->validateSubmission($batch);

            $batch->fill([
                'status' => 'submitted',
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_note' => null,
            ]);
            $batch->save();

            return $batch->fresh([
                'department',
                'submittedBy',
                'reviewedBy',
                'batchSlots.scheduleSlot.monthlySchedule.plan',
                'batchSlots.scheduleSlot.trainingClass',
                'batchSlots.scheduleSlot.teacher',
                'batchSlots.scheduleSlot.subjectModel.department',
                'batchSlots.scheduleSlot.subjectLesson',
                'batchSlots.scheduleSlot.room',
                'batchSlots.scheduleSlot.scheduleSlotGroup',
            ]);
        });
    }

    private function validateSubmission(DepartmentMonthlyAssignmentBatch $batch): void
    {
        $batch->loadMissing([
            'batchSlots.scheduleSlot.scheduleSlotGroup',
        ]);

        $subjectSlots = $batch->batchSlots
            ->map->scheduleSlot
            ->filter(fn ($slot): bool => $slot instanceof ScheduleSlot && ($slot->slot_type ?? 'subject') === 'subject')
            ->values();

        $errors = [];

        $unassignedTeacherCount = $subjectSlots
            ->filter(fn (ScheduleSlot $slot): bool => blank($slot->teacher_id))
            ->count();

        if ($unassignedTeacherCount > 0) {
            $errors['batch'] = sprintf(
                'Khong the gui PDT: con %d tiet chua duoc phan cong giang vien.',
                $unassignedTeacherCount
            );
        }

        if ($this->hasResourceConflict($subjectSlots, 'teacher_id')) {
            $errors['teacher_conflict'] = 'Khong the gui PDT: co tiet hoc bi trung giang vien.';
        }

        if ($this->hasResourceConflict($subjectSlots, 'room_id')) {
            $errors['room_conflict'] = 'Khong the gui PDT: co tiet hoc bi trung phong hoc.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param Collection<int, ScheduleSlot> $slots
     */
    private function hasResourceConflict(Collection $slots, string $field): bool
    {
        $slotsWithResource = $slots
            ->filter(function (ScheduleSlot $slot) use ($field): bool {
                $resourceId = $slot->{$field} ?? null;

                return $resourceId !== null && $resourceId !== '' && $slot->date !== null && $slot->period_number !== null;
            })
            ->values();

        if ($slotsWithResource->isEmpty()) {
            return false;
        }

        $batchSlotIds = $slotsWithResource->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $grouped = $slotsWithResource->groupBy(function (ScheduleSlot $slot) use ($field): string {
            return sprintf(
                '%d|%s|%d',
                (int) $slot->{$field},
                $slot->date->format('Y-m-d'),
                (int) $slot->period_number
            );
        });

        foreach ($grouped as $slotGroup) {
            $slotGroup = $slotGroup->values();
            if ($slotGroup->count() > 1 && ! $this->sharesAllowedActiveMergeGroup($slotGroup)) {
                return true;
            }

            /** @var ScheduleSlot $referenceSlot */
            $referenceSlot = $slotGroup->first();
            $resourceId = (int) $referenceSlot->{$field};
            $date = $referenceSlot->date->format('Y-m-d');
            $periodNumber = (int) $referenceSlot->period_number;

            $externalConflicts = ScheduleSlot::query()
                ->with('scheduleSlotGroup')
                ->where($field, $resourceId)
                ->whereDate('date', $date)
                ->where('period_number', $periodNumber)
                ->whereNotIn('id', $batchSlotIds)
                ->get()
                ->filter(fn (ScheduleSlot $conflictSlot): bool => ! $this->isSameActiveMergeGroup($referenceSlot, $conflictSlot));

            if ($externalConflicts->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Collection<int, ScheduleSlot> $slots
     */
    private function sharesAllowedActiveMergeGroup(Collection $slots): bool
    {
        $slots = $slots->values();
        if ($slots->isEmpty()) {
            return false;
        }

        $groupIds = $slots
            ->map(fn (ScheduleSlot $slot): ?int => $this->getActiveScheduleSlotGroupId($slot))
            ->filter(fn ($groupId): bool => $groupId !== null)
            ->unique()
            ->values();

        if ($groupIds->count() !== 1) {
            return false;
        }

        $groupId = (int) $groupIds->first();

        return $slots->every(
            fn (ScheduleSlot $slot): bool => $this->getActiveScheduleSlotGroupId($slot) === $groupId
        );
    }

    private function getActiveScheduleSlotGroupId(ScheduleSlot $slot): ?int
    {
        $group = $slot->scheduleSlotGroup;

        if (! $group || ($group->status ?? null) !== 'active') {
            return null;
        }

        return is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null;
    }

    private function isSameActiveMergeGroup(ScheduleSlot $first, ScheduleSlot $second): bool
    {
        $firstGroupId = $this->getActiveScheduleSlotGroupId($first);
        $secondGroupId = $this->getActiveScheduleSlotGroupId($second);

        if ($firstGroupId === null || $secondGroupId === null) {
            return false;
        }

        return $firstGroupId === $secondGroupId;
    }
}
