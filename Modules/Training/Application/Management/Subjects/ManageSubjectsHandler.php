<?php

namespace Modules\Training\Application\Management\Subjects;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\Subject;

class ManageSubjectsHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return Subject::class;
    }

    protected function relationships(): array
    {
        return ['department', 'lessons'];
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'status'];
    }

    protected function filterableColumns(): array
    {
        return ['status', 'department_id'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('subjects', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->ignore($id)],
            'total_periods' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}