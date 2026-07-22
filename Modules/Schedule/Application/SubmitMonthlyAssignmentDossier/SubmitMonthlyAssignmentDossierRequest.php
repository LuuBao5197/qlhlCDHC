<?php

namespace Modules\Schedule\Application\SubmitMonthlyAssignmentDossier;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMonthlyAssignmentDossierRequest extends FormRequest
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
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
