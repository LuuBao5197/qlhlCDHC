<?php

namespace Modules\Training\Application\Management\Students;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentsRequest extends FormRequest
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
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'class_id.required' => 'Vui lòng chọn lớp học.',
            'class_id.exists' => 'Lớp học đã chọn không tồn tại.',
            'import_file.required' => 'Vui lòng chọn file CSV.',
            'import_file.file' => 'File import không hợp lệ.',
            'import_file.mimes' => 'File import phải có định dạng CSV hoặc TXT.',
            'import_file.max' => 'File import không được lớn hơn 5 MB.',
        ];
    }
}
