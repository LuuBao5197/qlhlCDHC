<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Modules\Training\Models\DailyTrainingLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyTrainingLogController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return DailyTrainingLog::class;
    }

    protected function relationships(): array
    {
        return ['scheduleSlot', 'teacher', 'checkedBy'];
    }

    protected function searchColumns(): array
    {
        return ['result_status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'schedule_slot_id' => ['required', 'integer', 'exists:schedule_slots,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'actual_date' => ['nullable', 'date'],
            'actual_period_number' => ['nullable', 'integer', 'min:1', 'max:20'],
            'result_status' => ['required', 'string', Rule::in(['recorded', 'completed', 'missed', 'delayed'])],
            'actual_content' => ['nullable', 'string'],
            'issue_note' => ['nullable', 'string'],
            'checked_by' => ['nullable', 'integer', 'exists:users,id'],
            'checked_at' => ['nullable', 'date'],
        ];
    }
}
