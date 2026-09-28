<?php

namespace Modules\Schedule\Application\ExportDepartmentSchedule;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleViewRequest;
use Modules\Schedule\Models\MonthlySchedule;

/**
 * Cung quy tac phan quyen voi man hinh gan lich thang (authorize() ke thua).
 */
class ExportDepartmentScheduleRequest extends AssignMonthlyScheduleViewRequest
{
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(DepartmentScheduleExportPeriod::MODES)],
            'date' => ['nullable', 'date'],
            'department_id' => ['nullable', 'integer'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'issued_date' => ['nullable', 'date'],
            'signer_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'Vui lòng chọn chế độ xuất (ngày, tuần hoặc tháng).',
            'mode.in' => 'Chế độ xuất không hợp lệ.',
            'date.date' => 'Ngày chọn không hợp lệ.',
            'issued_date.date' => 'Ngày lập không hợp lệ.',
        ];
    }

    public function period(MonthlySchedule $anchor): DepartmentScheduleExportPeriod
    {
        $anchorDate = $this->filled('date')
            ? CarbonImmutable::parse($this->input('date'))
            : CarbonImmutable::create((int) $anchor->year, (int) $anchor->month, 1);

        return DepartmentScheduleExportPeriod::make((string) $this->input('mode'), $anchorDate);
    }

    public function issuedDate(): CarbonImmutable
    {
        return $this->filled('issued_date')
            ? CarbonImmutable::parse($this->input('issued_date'))->startOfDay()
            : CarbonImmutable::today();
    }
}
