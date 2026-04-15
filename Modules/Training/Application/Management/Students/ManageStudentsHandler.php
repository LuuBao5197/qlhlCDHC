<?php

namespace Modules\Training\Application\Management\Students;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\Student;

class ManageStudentsHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return Student::class;
    }

    protected function relationships(): array
    {
        return ['trainingClass'];
    }

    protected function searchColumns(): array
    {
        return ['student_code', 'name', 'status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'student_code' => ['required', 'string', 'max:255', Rule::unique('students', 'student_code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(['active', 'suspended', 'graduated'])],
        ];
    }
}