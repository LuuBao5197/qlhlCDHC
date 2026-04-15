<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Modules\Training\Models\ChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChangeRequestController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return ChangeRequest::class;
    }

    protected function relationships(): array
    {
        return ['monthlySchedule', 'scheduleSlot', 'requestedBy'];
    }

    protected function searchColumns(): array
    {
        return ['reason', 'status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'monthly_schedule_id' => ['required', 'integer', 'exists:monthly_schedules,id'],
            'schedule_slot_id' => ['nullable', 'integer', 'exists:schedule_slots,id'],
            'requested_by' => ['nullable', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string'],
            'old_payload' => ['nullable', 'array'],
            'new_payload' => ['nullable', 'array'],
            'status' => ['required', 'string', Rule::in(['pending', 'approved', 'rejected', 'resolved'])],
            'submitted_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
        ];
    }
}
