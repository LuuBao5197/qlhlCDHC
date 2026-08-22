<?php

namespace Modules\Training\Application\Management\Subjects;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;

class ManageSubjectsHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return Subject::class;
    }

    protected function relationships(): array
    {
        return ['department', 'lessons', 'trainingPrograms'];
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'status'];
    }

    protected function filterableColumns(): array
    {
        return ['status', 'department_id'];
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $model = Subject::create($this->withComposedCode($validated));

        return response()->json($this->freshModel($model), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate($this->rules($request, $id));
        $model = $this->findModel($id);
        $model->update($this->withComposedCode($validated, $id));

        return response()->json($this->freshModel($model));
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->ignore($id)],
            'total_periods' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * The subject code is always suffixed with the chosen department's code
     * (MãMH_MãKhoa), so the user cannot submit a code without it or edit it away.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function withComposedCode(array $validated, ?int $ignoreId = null): array
    {
        $department = Department::findOrFail($validated['department_id']);
        $existingDepartmentCodes = Department::query()->pluck('code')->all();

        $code = Subject::composeCode($validated['code'], $department->code, $existingDepartmentCodes);

        $duplicate = Subject::query()
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'code' => ["Mã môn học '{$code}' đã tồn tại."],
            ]);
        }

        $validated['code'] = $code;

        return $validated;
    }
}
