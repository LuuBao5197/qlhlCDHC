<?php

namespace Modules\Schedule\Application\UpdateSchedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $scheduleId = $this->route('id');

        return [
            'semester' => 'required|integer|min:1|max:2',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 10),
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom messages for validation.
     */
    public function messages(): array
    {
        return [
            'semester.required' => 'Semester là bắt buộc.',
            'year.required' => 'Năm là bắt buộc.',
        ];
    }
}
