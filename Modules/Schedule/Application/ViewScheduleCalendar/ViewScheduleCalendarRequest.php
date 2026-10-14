<?php

namespace Modules\Schedule\Application\ViewScheduleCalendar;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleExportPeriod;

class ViewScheduleCalendarRequest extends FormRequest
{
    public const GROUP_PERIOD = 'period';

    public const GROUP_CLASS = 'class';

    public const MODES =[DepartmentScheduleExportPeriod::MODE_DAY, DepartmentScheduleExportPeriod::MODE_WEEK];

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
            'group' => ['nullable', Rule::in([self::GROUP_PERIOD, self::GROUP_CLASS])],
        ];
    }

    /**
     * Cach nhom khi xem tuan: theo tiet (mac dinh) hoac theo lop (hang = lop, cot = ngay).
     * Chi co hieu luc voi xem tuan o pham vi toan truong/khoa.
     */
    public function group(): string
    {
        [$scope] = $this->resolvedScope();
        $canGroup = $this->mode() === DepartmentScheduleExportPeriod::MODE_WEEK
            && in_array($scope, [ScheduleCalendarAccess::SCOPE_SCHOOL, ScheduleCalendarAccess::SCOPE_DEPARTMENT], true);

        return $canGroup && $this->input('group') === self::GROUP_CLASS ? self::GROUP_CLASS : self::GROUP_PERIOD;
    }

    public function canGroup(): bool
    {
        [$scope] = $this->resolvedScope();

        return $this->mode() === DepartmentScheduleExportPeriod::MODE_WEEK
            && in_array($scope, [ScheduleCalendarAccess::SCOPE_SCHOOL, ScheduleCalendarAccess::SCOPE_DEPARTMENT], true);
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
