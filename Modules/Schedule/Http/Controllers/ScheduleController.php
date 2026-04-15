<?php

namespace Modules\Schedule\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Schedule\Models\MonthlySchedule;

class ScheduleController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return MonthlySchedule::class;
    }

    protected function relationships(): array
    {
        return ['plan', 'department', 'trainingClass', 'scheduleSlots'];
    }

    protected function searchColumns(): array
    {
        return ['class_name', 'status'];
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
            'status' => ['required', 'string', Rule::in(['draft', 'pending', 'processing', 'submitted', 'approved', 'rejected', 'returned'])],
            'submitted_at' => ['nullable', 'date'],
            'approved_at' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }
}