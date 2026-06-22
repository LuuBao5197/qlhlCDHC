<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;

class ApproveDepartmentMonthlyAssignmentBatch
{
    public function handle(DepartmentMonthlyAssignmentBatch $batch, User $actor): DepartmentMonthlyAssignmentBatch
    {
        return DB::transaction(function () use ($batch, $actor): DepartmentMonthlyAssignmentBatch {
            $lockedBatch = DepartmentMonthlyAssignmentBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'batch' => 'Chi co the duyet batch dang o trang thai submitted.',
                ]);
            }

            $lockedBatch->fill([
                'status' => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => null,
            ]);
            $lockedBatch->save();

            return $lockedBatch->fresh([
                'department',
                'submittedBy',
                'reviewedBy',
                'batchSlots.scheduleSlot.monthlySchedule.plan',
                'batchSlots.scheduleSlot.trainingClass',
                'batchSlots.scheduleSlot.teacher',
                'batchSlots.scheduleSlot.subjectModel.department',
                'batchSlots.scheduleSlot.subjectLesson',
                'batchSlots.scheduleSlot.room',
                'batchSlots.scheduleSlot.scheduleSlotGroup',
            ]);
        });
    }
}
