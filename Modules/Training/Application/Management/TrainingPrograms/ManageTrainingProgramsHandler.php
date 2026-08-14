<?php

namespace Modules\Training\Application\Management\TrainingPrograms;

use Illuminate\Http\JsonResponse;
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
        return ['batches', 'subjects'];
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
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);

        $model = TrainingProgram::query()->create($validated);
        $model->subjects()->sync($subjectIds);

        return response()->json($this->freshModel($model), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->findModel($id);
        $validated = $request->validate($this->rules($request, $id));
        $subjectIds = $validated['subject_ids'] ?? null;
        unset($validated['subject_ids']);

        $model->update($validated);
        if ($subjectIds !== null) {
            $model->subjects()->sync($subjectIds);
        }

        return response()->json($this->freshModel($model));
    }
}
