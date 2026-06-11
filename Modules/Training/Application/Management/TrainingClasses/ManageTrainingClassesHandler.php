<?php

namespace Modules\Training\Application\Management\TrainingClasses;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\TrainingClass;

class ManageTrainingClassesHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return TrainingClass::class;
    }

    protected function relationships(): array
    {
        return ['students', 'trainingBatch'];
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('classes', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('classes', 'name')->ignore($id)],
            'training_batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'course_year' => ['nullable', 'integer', 'min:2000', 'max:' . ((int) date('Y') + 10)],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }
}
