<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Services\InternalNotificationService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;

/**
 * Buoc "Lanh dao Khoa tu choi" batch cua chinh Khoa minh.
 * Tra ve draft: Khoa phai sua va duoc Lanh dao Khoa duyet lai tu dau truoc khi den PDT.
 */
class ReturnDepartmentMonthlyAssignmentBatch
{
    public function handle(DepartmentMonthlyAssignmentBatch $batch, User $actor, string $reviewNote): DepartmentMonthlyAssignmentBatch
    {
        return DB::transaction(function () use ($batch, $actor, $reviewNote): DepartmentMonthlyAssignmentBatch {
            $lockedBatch = DepartmentMonthlyAssignmentBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== DepartmentMonthlyAssignmentBatch::STATUS_SUBMITTED
                || $lockedBatch->current_step !== DepartmentMonthlyAssignmentBatch::STEP_DEPARTMENT_REVIEW) {
                throw ValidationException::withMessages([
                    'batch' => 'Chi co the tra batch ve khi dang cho Lanh dao Khoa duyet.',
                ]);
            }

            $lockedBatch->fill([
                'status' => DepartmentMonthlyAssignmentBatch::STATUS_RETURNED,
                'current_step' => DepartmentMonthlyAssignmentBatch::STEP_DRAFT,
                'department_reviewed_by' => $actor->id,
                'department_reviewed_at' => now(),
                'department_review_note' => $reviewNote,
            ]);
            $lockedBatch->save();

            app(InternalNotificationService::class)->notifyDepartmentMonthlyAssignmentBatchReviewed($lockedBatch, $actor, false);

            return $lockedBatch->fresh([
                'department',
                'submittedBy',
                'departmentReviewedBy',
                'trainingOfficeReviewedBy',
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
