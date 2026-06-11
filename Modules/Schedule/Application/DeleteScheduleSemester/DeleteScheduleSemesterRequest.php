<?php

namespace Modules\Schedule\Application\DeleteScheduleSemester;

use Illuminate\Foundation\Http\FormRequest;

class DeleteScheduleSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && ($user->isTrainingOffice() || $user->isAdmin());
    }

    public function rules(): array
    {
        return [
            'confirmed' => 'nullable|boolean',
        ];
    }
}
