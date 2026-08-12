<?php

namespace Modules\Training\Application\Management\TrainingBatches;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\TrainingBatch;

class ManageTrainingBatchesHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return TrainingBatch::class;
    }

    protected function relationships(): array
    {
        return ['trainingProgram', 'classes'];
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'status'];
    }

    protected function filterableColumns(): array
    {
        return ['status', 'training_program_id'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'training_program_id' => ['required', 'integer', 'exists:training_programs,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('training_batches', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }
}
