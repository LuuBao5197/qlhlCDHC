<?php

namespace Modules\Schedule\Application\ReviewChangeRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewChangeRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isTrainingOffice() || $user->isAdmin());
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
            'apply_changes' => ['nullable', 'boolean'],
        ];
    }
}
