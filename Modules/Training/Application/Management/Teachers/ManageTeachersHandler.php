<?php

namespace Modules\Training\Application\Management\Teachers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\Teacher;

class ManageTeachersHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return Teacher::class;
    }

    protected function relationships(): array
    {
        return ['department'];
    }

    protected function searchColumns(): array
    {
        return ['teacher_code', 'name', 'status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'teacher_code' => ['required', 'string', 'max:255', Rule::unique('teachers', 'teacher_code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }
}