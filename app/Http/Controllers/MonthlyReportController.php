<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Modules\Training\Models\MonthlyReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonthlyReportController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return MonthlyReport::class;
    }

    protected function relationships(): array
    {
        return ['monthlySchedule', 'createdBy', 'approvedBy'];
    }

    protected function searchColumns(): array
    {
        return ['status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'monthly_schedule_id' => ['required', 'integer', 'exists:monthly_schedules,id'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'summary' => ['nullable', 'string'],
            'result_overview' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['draft', 'submitted', 'approved', 'rejected'])],
            'submitted_at' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
        ];
    }
}
