<?php

namespace Modules\Schedule\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Illuminate\Http\Request;
use Modules\Schedule\Models\MonthlySchedule;

class ScheduleController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return MonthlySchedule::class;
    }

    protected function relationships(): array
    {
        return ['plan', 'trainingClass', 'scheduleSlots'];
    }

    protected function searchColumns(): array
    {
        return ['class_name'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'class_name' => ['required', 'string', 'max:255'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . ((int) date('Y') + 10)],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
