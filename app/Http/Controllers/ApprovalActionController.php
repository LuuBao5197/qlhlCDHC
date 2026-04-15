<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Scaffold\ApiCrudController;
use Modules\Training\Models\ApprovalAction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovalActionController extends ApiCrudController
{
    protected function modelClass(): string
    {
        return ApprovalAction::class;
    }

    protected function relationships(): array
    {
        return ['approvalRequest', 'actedBy'];
    }

    protected function searchColumns(): array
    {
        return ['step_code', 'action'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'approval_request_id' => ['required', 'integer', 'exists:approval_requests,id'],
            'step_code' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', Rule::in(['submit', 'approve', 'reject', 'return', 'comment'])],
            'acted_by' => ['nullable', 'integer', 'exists:users,id'],
            'acted_at' => ['nullable', 'date'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
