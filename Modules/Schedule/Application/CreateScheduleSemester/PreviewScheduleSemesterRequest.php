<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use Illuminate\Foundation\Http\FormRequest;

class PreviewScheduleSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ($this->user()->isTrainingOffice() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',

            // Sent either as a JSON-encoded string (multipart form-data,
            // used when an import file is attached) or as a native array
            // (application/json body). Decoded/normalized in the handler.
            'rules' => 'nullable',
            'class_events' => 'nullable',
            'global_events' => 'nullable',

            'class_code' => 'nullable|string|max:255',
            'import_file' => 'nullable|file|mimes:csv,txt,xlsx|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.mimes' => 'File import phai o dinh dang Excel (.xlsx) hoac CSV/TXT.',
            'import_file.max' => 'File import khong duoc vuot qua 10MB.',
        ];
    }
}
