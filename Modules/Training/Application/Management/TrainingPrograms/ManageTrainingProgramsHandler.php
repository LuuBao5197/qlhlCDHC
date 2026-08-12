<?php

namespace Modules\Training\Application\Management\TrainingPrograms;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\TrainingProgram;

class ManageTrainingProgramsHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return TrainingProgram::class;
    }

    protected function relationships(): array
    {
        return ['batches'];
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'status'];
    }

    protected function filterableColumns(): array
    {
        return ['status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('training_programs', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }
}
