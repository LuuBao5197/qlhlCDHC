<?php

namespace Modules\Training\Application\Management\TrainingClasses;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Application\Management\Shared\TrainingBatchPlanLockChecker;
use Modules\Training\Models\TrainingClass;

class ManageTrainingClassesHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return TrainingClass::class;
    }

    protected function relationships(): array
    {
        return ['students', 'trainingBatch', 'defaultRoom'];
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
            'training_batch_id' => [
                'required', 'integer', 'exists:training_batches,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($id): void {
                    $this->guardTrainingBatchChange($id, (int) $value, $fail);
                },
            ],
            'course_year' => ['nullable', 'integer', 'min:2000', 'max:' . ((int) date('Y') + 10)],
            'total_students' => ['required', 'integer', 'min:0'],
            'default_room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }

    /**
     * A training_batch with a submitted/approved semester plan is locked for class
     * membership changes: adding a class to it, or moving a class out of it, would
     * leave that plan's class list out of sync with what was actually reviewed.
     */
    private function guardTrainingBatchChange(?int $classId, int $newBatchId, \Closure $fail): void
    {
        $checker = new TrainingBatchPlanLockChecker();
        $currentBatchId = $classId !== null
            ? TrainingClass::query()->find($classId)?->training_batch_id
            : null;
        $currentBatchId = $currentBatchId !== null ? (int) $currentBatchId : null;

        if ($currentBatchId === $newBatchId) {
            return;
        }

        if ($currentBatchId !== null && $checker->isLocked($currentBatchId)) {
            $fail('Lớp này đang thuộc một khóa đào tạo đã có kế hoạch học kỳ đang chờ duyệt hoặc đã duyệt, không thể đổi sang khóa khác.');

            return;
        }

        if ($checker->isLocked($newBatchId)) {
            $fail('Khóa đào tạo đã chọn đã có kế hoạch học kỳ đang chờ duyệt hoặc đã duyệt, không thể thêm lớp mới vào khóa này.');
        }
    }
}
