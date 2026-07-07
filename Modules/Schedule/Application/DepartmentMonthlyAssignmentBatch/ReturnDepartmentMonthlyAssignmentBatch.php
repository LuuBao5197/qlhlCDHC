<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Services\InternalNotificationService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;

class ReturnDepartmentMonthlyAssignmentBatch
{
    public function handle(DepartmentMonthlyAssignmentBatch $batch, User $actor, string $reviewNote): DepartmentMonthlyAssignmentBatch
    {
        return DB::transaction(function () use ($batch, $actor, $reviewNote): DepartmentMonthlyAssignmentBatch {
            $lockedBatch = DepartmentMonthlyAssignmentBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'batch' => 'Chi co the tra batch ve khi dang o trang thai submitted.',
                ]);
            }

            $lockedBatch->fill([
                'status' => 'returned',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
            ]);
            $lockedBatch->save();

            app(InternalNotificationService::class)->notifyDepartmentMonthlyAssignmentBatchReviewed($lockedBatch, $actor, false);

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
