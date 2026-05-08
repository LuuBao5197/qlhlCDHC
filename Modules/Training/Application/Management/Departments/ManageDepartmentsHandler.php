<?php

namespace Modules\Training\Application\Management\Departments;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\Department;

class ManageDepartmentsHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return Department::class;
    }

    protected function relationships(): array
    {
        return ['subjects'];
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('departments', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
