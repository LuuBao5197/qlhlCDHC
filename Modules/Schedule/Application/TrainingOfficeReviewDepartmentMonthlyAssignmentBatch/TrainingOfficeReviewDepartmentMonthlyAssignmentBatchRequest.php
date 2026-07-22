<?php

namespace Modules\Schedule\Application\TrainingOfficeReviewDepartmentMonthlyAssignmentBatch;

use App\Services\ApprovalAuthorityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingOfficeReviewDepartmentMonthlyAssignmentBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(ApprovalAuthorityService $approvalAuthority): bool
    {
        return $approvalAuthority->canApproveAsTrainingOffice($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
