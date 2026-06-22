<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlySchedule;

class SubmitDepartmentMonthlyAssignmentBatch
{
    public function __construct(
        private BuildOrRefreshDraftBatch $buildOrRefreshDraftBatch
    ) {}

    public function handle(MonthlySchedule $anchorMonthlySchedule, User $actor): DepartmentMonthlyAssignmentBatch
    {
        return DB::transaction(function () use ($anchorMonthlySchedule, $actor): DepartmentMonthlyAssignmentBatch {
            $batch = $this->buildOrRefreshDraftBatch->handle($anchorMonthlySchedule, $actor);

            if (! in_array($batch->status, ['draft', 'returned'], true)) {
                throw ValidationException::withMessages([
                    'batch' => 'Chi co the gui batch khi dang o trang thai draft hoac returned.',
                ]);
            }

            $batch->fill([
                'status' => 'submitted',
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_note' => null,
            ]);
            $batch->save();

            return $batch->fresh([
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
