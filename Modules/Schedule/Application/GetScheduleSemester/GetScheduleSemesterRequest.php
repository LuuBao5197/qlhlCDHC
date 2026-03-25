<?php

namespace Modules\Schedule\Application\GetScheduleSemester;

use Illuminate\Foundation\Http\FormRequest;

class GetScheduleSemesterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public access
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'semester' => 'nullable|integer|min:1|max:2',
            'year' => 'nullable|integer|min:2000',
            'className' => 'nullable|string|max:50',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Get route parameters
        $this->merge([
            'semester' => $this->semester ?? $this->route('semester'),
            'year' => $this->year ?? $this->route('year'),
            'className' => $this->className ?? $this->route('className'),
        ]);
    }
}
