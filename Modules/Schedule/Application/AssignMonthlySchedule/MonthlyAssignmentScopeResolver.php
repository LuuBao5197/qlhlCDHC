<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Training\Models\Subject;

class MonthlyAssignmentScopeResolver
{
    /**
     * Resolve the aggregate scope for the current monthly assignment screen.
     *
     * @return array{
     *     department_id:int,
     *     department_name:string,
     *     monthly_schedule_ids:array<int>,
     *     monthly_schedule_count:int,
     *     plan_count:int,
     *     department_subject_ids:array<int>,
     *     monthly_schedules:Collection<int, MonthlySchedule>
     * }|null
     */
    public function resolve(MonthlySchedule $anchorMonthlySchedule, ?User $user = null): ?array
    {
        $anchorMonthlySchedule->loadMissing('scheduleSlots.subjectModel.department');

        $departmentId = $this->resolveDepartmentId($anchorMonthlySchedule, $user);
        if ($departmentId === null) {
            return null;
        }

        $monthlySchedules = MonthlySchedule::query()
            ->with(['plan', 'scheduleSlots.subjectModel.department'])
            ->where('month', (int) $anchorMonthlySchedule->month)
            ->where('year', (int) $anchorMonthlySchedule->year)
            ->whereHas('scheduleSlots.subjectModel', function ($query) use ($departmentId): void {
                $query->where('department_id', $departmentId);
            })
            ->orderBy('plan_id')
            ->orderBy('id')
            ->get();

        if ($monthlySchedules->isEmpty()) {
            return null;
        }

        $departmentSubjectIds = Subject::query()
            ->where('department_id', $departmentId)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        $departmentName = $this->resolveDepartmentName($monthlySchedules, $anchorMonthlySchedule, $departmentId);

        return [
            'department_id' => $departmentId,
            'department_name' => $departmentName,
            'monthly_schedule_ids' => $monthlySchedules->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->values()
                ->all(),
            'monthly_schedule_count' => $monthlySchedules->count(),
            'plan_count' => $monthlySchedules->pluck('plan_id')
                ->filter()
                ->unique()
                ->count(),
            'department_subject_ids' => $departmentSubjectIds,
            'monthly_schedules' => $monthlySchedules,
        ];
    }

    private function resolveDepartmentId(MonthlySchedule $anchorMonthlySchedule, ?User $user = null): ?int
    {
        if ($user?->isDepartmentStaff() && $user->department_id !== null) {
            return (int) $user->department_id;
        }

        $departmentId = $anchorMonthlySchedule->scheduleSlots
            ->filter(fn ($slot) => $slot->subjectModel?->department_id !== null)
            ->map(fn ($slot) => (int) $slot->subjectModel->department_id)
            ->first();

        if (is_numeric($departmentId) && (int) $departmentId > 0) {
            return (int) $departmentId;
        }

        if ($user?->department_id !== null) {
            return (int) $user->department_id;
        }

        return null;
    }

    /**
     * @param Collection<int, MonthlySchedule> $monthlySchedules
     */
    private function resolveDepartmentName(Collection $monthlySchedules, MonthlySchedule $anchorMonthlySchedule, int $departmentId): string
    {
        $departmentName = $monthlySchedules
            ->flatMap(fn (MonthlySchedule $monthlySchedule) => $monthlySchedule->scheduleSlots ?? collect())
            ->first(fn ($slot) => (int) ($slot->subjectModel?->department_id ?? 0) === $departmentId)
            ?->subjectModel?->department?->name;

        if ($departmentName === null) {
            $departmentName = $anchorMonthlySchedule->scheduleSlots
                ->first(fn ($slot) => (int) ($slot->subjectModel?->department_id ?? 0) === $departmentId)
                ?->subjectModel?->department?->name;
        }

        if (is_string($departmentName) && trim($departmentName) !== '') {
            return trim($departmentName);
        }

        return '-';
    }
}
