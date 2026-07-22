<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Services\InternalNotificationService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;

/**
 * Buoc "Lanh dao Khoa duyet" batch cua chinh Khoa minh.
 * Duyet xong batch KHONG ket thuc: chuyen sang buoc PDT duyet (current_step=training_office_review).
 */
class ApproveDepartmentMonthlyAssignmentBatch
{
    public function handle(DepartmentMonthlyAssignmentBatch $batch, User $actor): DepartmentMonthlyAssignmentBatch
    {
        return DB::transaction(function () use ($batch, $actor): DepartmentMonthlyAssignmentBatch {
            $lockedBatch = DepartmentMonthlyAssignmentBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== DepartmentMonthlyAssignmentBatch::STATUS_SUBMITTED
                || $lockedBatch->current_step !== DepartmentMonthlyAssignmentBatch::STEP_DEPARTMENT_REVIEW) {
                throw ValidationException::withMessages([
                    'batch' => 'Chi co the duyet batch dang cho Lanh dao Khoa duyet.',
                ]);
            }

            $lockedBatch->fill([
                'current_step' => DepartmentMonthlyAssignmentBatch::STEP_TRAINING_OFFICE_REVIEW,
                'department_reviewed_by' => $actor->id,
                'department_reviewed_at' => now(),
                'department_review_note' => null,
            ]);
            $lockedBatch->save();

            app(InternalNotificationService::class)->notifyDepartmentMonthlyAssignmentBatchReviewed($lockedBatch, $actor, true);

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
