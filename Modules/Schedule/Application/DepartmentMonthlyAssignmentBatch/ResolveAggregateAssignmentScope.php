<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Models\User;
use Modules\Schedule\Application\AssignMonthlySchedule\MonthlyAssignmentScopeResolver;
use Modules\Schedule\Models\MonthlySchedule;

class ResolveAggregateAssignmentScope
{
    public function __construct(
        private MonthlyAssignmentScopeResolver $scopeResolver
    ) {}

    /**
     * @return array{
     *     department_id:int,
     *     department_name:string,
     *     monthly_schedule_ids:array<int>,
     *     monthly_schedule_count:int,
     *     plan_count:int,
     *     department_subject_ids:array<int>,
     *     monthly_schedules:\Illuminate\Support\Collection<int, MonthlySchedule>
     * }|null
     */
    public function handle(MonthlySchedule $anchorMonthlySchedule, ?User $user = null, ?int $requestedDepartmentId = null): ?array
    {
        return $this->scopeResolver->resolve($anchorMonthlySchedule, $user, $requestedDepartmentId);
    }
}
