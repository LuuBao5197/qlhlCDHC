<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Modules\Training\Models\SlotEvaluation;
use Illuminate\Http\Request;

class SlotEvaluationController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return SlotEvaluation::class;
    }

    protected function relationships(): array
    {
        return ['scheduleSlot', 'evaluator'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'schedule_slot_id' => ['required', 'integer', 'exists:schedule_slots,id'],
            'evaluator_id' => ['nullable', 'integer', 'exists:users,id'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'absent_count' => ['nullable', 'integer', 'min:0'],
            'rating_level' => ['required', 'in:tot,kha,trung_binh,yeu'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
