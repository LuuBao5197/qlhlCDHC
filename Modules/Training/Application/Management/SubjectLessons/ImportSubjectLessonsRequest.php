<?php

namespace Modules\Training\Application\Management\SubjectLessons;

use Illuminate\Foundation\Http\FormRequest;

class ImportSubjectLessonsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Vui lòng chọn file CSV.',
            'import_file.file' => 'File import không hợp lệ.',
            'import_file.mimes' => 'File import phải có định dạng CSV hoặc TXT.',
            'import_file.max' => 'File import không được lớn hơn 5 MB.',
        ];
    }
}
