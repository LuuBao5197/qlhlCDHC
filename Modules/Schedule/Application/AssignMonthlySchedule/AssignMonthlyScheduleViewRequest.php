<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Schedule\Models\MonthlySchedule;

class AssignMonthlyScheduleViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        if (!$user->isDepartmentStaff()) {
            return false;
        }

        $scheduleId = (int) $this->route('id');
        if ($scheduleId <= 0) {
            return true;
        }

        $monthlySchedule = MonthlySchedule::query()
            ->with('trainingClass')
            ->find($scheduleId);

        if (!$monthlySchedule) {
            return false;
        }

        $departmentId = $monthlySchedule->department_id
            ?? $monthlySchedule->trainingClass?->department_id;

        if ($departmentId === null || $user->department_id === null) {
            return true;
        }

        return (int) $departmentId === (int) $user->department_id;
    }

    public function rules(): array
    {
        return [];
    }
}
