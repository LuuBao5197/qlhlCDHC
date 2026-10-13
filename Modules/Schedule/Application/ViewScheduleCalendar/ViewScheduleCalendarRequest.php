<?php

namespace Modules\Schedule\Application\ViewScheduleCalendar;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleExportPeriod;

class ViewScheduleCalendarRequest extends FormRequest
{
    public const MODES = [DepartmentScheduleExportPeriod::MODE_DAY, DepartmentScheduleExportPeriod::MODE_WEEK];

    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        [$scope, $scopeId] = $this->resolvedScope();

        return app(ScheduleCalendarAccess::class)->canView($user, $scope, $scopeId);
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::in(self::MODES)],
            'date' => ['nullable', 'date'],
            'scope' => ['nullable', Rule::in(ScheduleCalendarAccess::SCOPES)],
            'id' => ['nullable', 'integer'],
        ];
    }

    public function mode(): string
    {
        return (string) $this->input('mode', DepartmentScheduleExportPeriod::MODE_WEEK);
    }

    public function period(): DepartmentScheduleExportPeriod
    {
        $date = $this->filled('date') ? CarbonImmutable::parse($this->input('date')) : CarbonImmutable::today();

        return DepartmentScheduleExportPeriod::make($this->mode(), $date);
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    public function resolvedScope(): array
    {
        $access = app(ScheduleCalendarAccess::class);

        $scope = (string) $this->input('scope', '');
        if (! in_array($scope, ScheduleCalendarAccess::SCOPES, true)) {
            return $access->defaultScope($this->user());
        }

        $id = $this->filled('id') ? (int) $this->input('id') : null;
        if ($scope === ScheduleCalendarAccess::SCOPE_DEPARTMENT && $id === null) {
            $id = $access->departmentIds($this->user())[0] ?? null;
        }

        return [$scope, $id];
    }
}
