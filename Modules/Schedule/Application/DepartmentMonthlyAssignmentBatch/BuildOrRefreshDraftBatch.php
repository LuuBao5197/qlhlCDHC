<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatchSlot;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;
use App\Models\User;

class BuildOrRefreshDraftBatch
{
    public function __construct(
        private ResolveAggregateAssignmentScope $scopeResolver
    ) {}

    /**
     * Build or refresh the draft snapshot for a department-month batch.
     * Must be called inside an existing transaction when atomicity is required.
     */
    public function handle(MonthlySchedule $anchorMonthlySchedule, User $actor): DepartmentMonthlyAssignmentBatch
    {
        $scope = $this->scopeResolver->handle($anchorMonthlySchedule, $actor);
        if ($scope === null) {
            throw ValidationException::withMessages([
                'scope' => 'Khong xac dinh duoc khoa hien tai de tao batch tong hop.',
            ]);
        }

        $departmentId = (int) $scope['department_id'];
        $month = (int) $anchorMonthlySchedule->month;
        $year = (int) $anchorMonthlySchedule->year;

        $batch = DepartmentMonthlyAssignmentBatch::query()
            ->where('department_id', $departmentId)
            ->where('month', $month)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($batch && ! in_array($batch->status, ['draft', 'returned'], true)) {
            throw ValidationException::withMessages([
                'batch' => 'Khong the cap nhat batch da gui/da duyet. Hay tao phien ban moi neu can chinh sua.',
            ]);
        }

        $scopeSlots = $this->loadScopeSlots($scope);
        if ($scopeSlots->isEmpty()) {
            throw ValidationException::withMessages([
                'batch' => 'Khong co tiet mon hoc nao thuoc khoa hien tai trong thang nay.',
            ]);
        }

        $this->validateActiveMergeGroups($scopeSlots);

        if (! $batch) {
            $batch = new DepartmentMonthlyAssignmentBatch();
            $batch->department_id = $departmentId;
            $batch->month = $month;
            $batch->year = $year;
            $batch->status = 'draft';
            $batch->version = 1;
        } else {
            $batch->version = (int) ($batch->version ?? 1) + 1;
            $batch->status = 'draft';
        }

        $batch->submitted_by = null;
        $batch->submitted_at = null;
        $batch->reviewed_by = null;
        $batch->reviewed_at = null;
        $batch->review_note = null;
        $batch->save();

        $batch->batchSlots()->delete();

        $now = now();
        $batch->batchSlots()->createMany(
            $scopeSlots
                ->pluck('id')
                ->map(static fn (int $slotId): array => [
                    'schedule_slot_id' => $slotId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );

        return $batch->load([
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
    }

    /**
     * @param array{
     *     monthly_schedule_ids:array<int>,
     *     department_subject_ids:array<int>
     * } $scope
     */
    private function loadScopeSlots(array $scope): Collection
    {
        $monthlyScheduleIds = $scope['monthly_schedule_ids'];
        $departmentSubjectIds = $scope['department_subject_ids'];

        return ScheduleSlot::query()
            ->with([
                'monthlySchedule.plan',
                'trainingClass',
                'teacher',
                'subjectModel.department',
                'subjectLesson',
                'room',
                'scheduleSlotGroup.scheduleSlots',
            ])
            ->whereIn('monthly_schedule_id', $monthlyScheduleIds)
            ->where('slot_type', 'subject')
            ->when(
                $departmentSubjectIds !== [],
                fn ($query) => $query->whereIn('subject_id', $departmentSubjectIds)
            )
            ->orderBy('date')
            ->orderBy('period_number')
            ->orderBy('monthly_schedule_id')
            ->orderBy('class_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function validateActiveMergeGroups(Collection $scopeSlots): void
    {
        $scopeSlotIds = $scopeSlots->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $activeGroupIds = $scopeSlots
            ->filter(fn (ScheduleSlot $slot) => $slot->scheduleSlotGroup?->status === 'active')
            ->pluck('schedule_slot_group_id')
            ->filter(fn ($groupId) => is_numeric($groupId))
            ->map(static fn ($groupId) => (int) $groupId)
            ->unique()
            ->values();

        if ($activeGroupIds->isEmpty()) {
            return;
        }

        $groups = ScheduleSlotGroup::query()
            ->with(['scheduleSlots'])
            ->whereIn('id', $activeGroupIds)
            ->lockForUpdate()
            ->get();

        foreach ($groups as $group) {
            if ($group->status !== 'active') {
                continue;
            }

            $missingSlotIds = $group->scheduleSlots
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->reject(fn (int $slotId) => in_array($slotId, $scopeSlotIds, true))
                ->values();

            if ($missingSlotIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'batch' => sprintf(
                        'Nhom ghep lop #%d chua du tat ca thanh vien trong batch tong hop.',
                        $group->id
                    ),
                ]);
            }
        }
    }
}
