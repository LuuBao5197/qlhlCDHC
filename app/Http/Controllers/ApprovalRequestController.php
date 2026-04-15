<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Modules\Training\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovalRequestController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return ApprovalRequest::class;
    }

    protected function relationships(): array
    {
        return ['submittedBy', 'actions'];
    }

    protected function searchColumns(): array
    {
        return ['entity_type', 'current_step', 'status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'entity_type' => ['required', 'string', 'max:255'],
            'entity_id' => ['required', 'integer', 'min:1'],
            'submitted_by' => ['nullable', 'integer', 'exists:users,id'],
            'current_step' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['pending', 'processing', 'approved', 'rejected', 'returned'])],
            'submitted_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
