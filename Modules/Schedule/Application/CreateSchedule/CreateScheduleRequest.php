<?php

namespace Modules\Schedule\Application\CreateSchedule;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
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
        return [
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date',
            'class_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Custom messages for validation.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu phải là ngày hợp lệ.',
            'start_date.before' => 'Ngày bắt đầu phải trước ngày kết thúc.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc phải là ngày hợp lệ.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'class_name.required' => 'Tên lớp là bắt buộc.',
            'class_name.string' => 'Tên lớp phải là chuỗi ký tự.',
            'class_name.max' => 'Tên lớp không được vượt quá 50 ký tự.',
        ];
    }
}
