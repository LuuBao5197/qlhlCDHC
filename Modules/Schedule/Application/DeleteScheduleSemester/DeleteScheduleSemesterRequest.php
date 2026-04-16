<?php

namespace Modules\Schedule\Application\DeleteScheduleSemester;

use Illuminate\Foundation\Http\FormRequest;

class DeleteScheduleSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'confirmed' => 'nullable|boolean',
        ];
    }
}
