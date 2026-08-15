<?php

namespace Modules\Schedule\Application\CancelSlotsForHoliday;

use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\HolidayCalendar;
use Throwable;

/**
 * Huy cac tiet mon hoc chua giang day trung ngay nghi le/tet dot xuat.
 * Khong tu tim ngay bu: Khoa se chu dong phan cong lai cac tiet con lai trong thang
 * (rut gon bai de, cat tiet tu nghien cuu/on tap...) roi gui lai qua dung quy trinh
 * duyet phan cong thang hien co (Lanh dao Khoa -> PDT -> Ban Giam hieu).
 */
class CancelSlotsForHolidayHandler
{
    public function handle(CancelSlotsForHolidayRequest $request, int $holidayCalendarId)
    {
        $holiday = HolidayCalendar::query()->findOrFail($holidayCalendarId);
        $actor = $request->user();

        try {
            $result = DB::transaction(function () use ($holiday, $actor) {
                return $this->cancelSlotsAndReopenWorkflow($holiday, $actor);
            });
        } catch (Throwable $exception) {
            return back()->with('error', 'Khong the huy tiet trung ngay nghi: ' . $exception->getMessage());
        }

        return back()->with('success', $this->buildSummaryMessage($holiday, $result));
    }

    /**
     * @return array{
     *     cancelled_count:int,
     *     already_taught: Collection<int, ScheduleSlot>,
     *     reopened_batches: Collection<int, DepartmentMonthlyAssignmentBatch>,
     *     reopened_dossiers: Collection<int, MonthlyAssignmentDossier>,
     * }
     */
    private function cancelSlotsAndReopenWorkflow(HolidayCalendar $holiday, User $actor): array
    {
        $candidateSlots = ScheduleSlot::query()
            ->with('dailyTrainingLogs')
            ->where('slot_type', 'subject')
            ->whereDate('date', '>=', $holiday->start_date->format('Y-m-d'))
            ->whereDate('date', '<=', $holiday->end_date->format('Y-m-d'))
            ->where('slot_status', '!=', 'cancelled')
            ->lockForUpdate()
            ->get();

        $alreadyTaught = $candidateSlots->filter(
            fn (ScheduleSlot $slot): bool => $slot->dailyTrainingLogs->isNotEmpty()
        )->values();

        $cancellableSlots = $candidateSlots->reject(
            fn (ScheduleSlot $slot): bool => $slot->dailyTrainingLogs->isNotEmpty()
        )->values();

        if ($cancellableSlots->isEmpty()) {
            return [
                'cancelled_count' => 0,
                'already_taught' => $alreadyTaught,
                'reopened_batches' => collect(),
                'reopened_dossiers' => collect(),
            ];
        }

        $now = now();
        foreach ($cancellableSlots as $slot) {
            $note = trim(((string) $slot->note !== '' ? $slot->note . ' | ' : '') . 'Huy do nghi le: ' . $holiday->name);

            $slot->forceFill([
                'slot_status' => 'cancelled',
                'holiday_calendar_id' => $holiday->id,
                'note' => $note,
                'updated_at' => $now,
            ])->save();
        }

        $slotIds = $cancellableSlots->pluck('id')->all();

        $reopenedBatches = $this->reopenAffectedBatches($slotIds, $holiday, $actor);
        $reopenedDossiers = $this->reopenAffectedDossiers($reopenedBatches, $holiday, $actor);

        return [
            'cancelled_count' => count($slotIds),
            'already_taught' => $alreadyTaught,
            'reopened_batches' => $reopenedBatches,
            'reopened_dossiers' => $reopenedDossiers,
        ];
    }

    /**
     * @param array<int> $slotIds
     * @return Collection<int, DepartmentMonthlyAssignmentBatch>
     */
    private function reopenAffectedBatches(array $slotIds, HolidayCalendar $holiday, User $actor): Collection
    {
        $batches = DepartmentMonthlyAssignmentBatch::query()
            ->whereHas('scheduleSlots', fn ($query) => $query->whereIn('schedule_slots.id', $slotIds))
            ->whereIn('status', [
                DepartmentMonthlyAssignmentBatch::STATUS_SUBMITTED,
                DepartmentMonthlyAssignmentBatch::STATUS_APPROVED,
            ])
            ->lockForUpdate()
            ->get();

        $reviewNote = 'Mo lai tu dong do nghi le "' . $holiday->name . '" phat sinh trong thang.';

        foreach ($batches as $batch) {
            $batch->update([
                'status' => DepartmentMonthlyAssignmentBatch::STATUS_RETURNED,
                'current_step' => DepartmentMonthlyAssignmentBatch::STEP_DRAFT,
                'department_reviewed_by' => null,
                'department_reviewed_at' => null,
                'department_review_note' => $reviewNote,
                'training_office_reviewed_by' => null,
                'training_office_reviewed_at' => null,
                'training_office_review_note' => null,
            ]);

            app(InternalNotificationService::class)->notifyDepartmentMonthlyAssignmentBatchReopenedForHoliday(
                $batch->fresh(['department', 'submittedBy']),
                $actor,
                $holiday->name
            );
        }

        return $batches;
    }

    /**
     * @param Collection<int, DepartmentMonthlyAssignmentBatch> $reopenedBatches
     * @return Collection<int, MonthlyAssignmentDossier>
     */
    private function reopenAffectedDossiers(Collection $reopenedBatches, HolidayCalendar $holiday, User $actor): Collection
    {
        if ($reopenedBatches->isEmpty()) {
            return collect();
        }

        $batchIds = $reopenedBatches->pluck('id')->all();

        $dossiers = MonthlyAssignmentDossier::query()
            ->whereHas('batches', fn ($query) => $query->whereIn('department_monthly_assignment_batches.id', $batchIds))
            ->whereIn('status', [
                MonthlyAssignmentDossier::STATUS_SUBMITTED,
                MonthlyAssignmentDossier::STATUS_APPROVED,
            ])
            ->lockForUpdate()
            ->get();

        foreach ($dossiers as $dossier) {
            $dossier->update([
                'status' => MonthlyAssignmentDossier::STATUS_RETURNED,
                'current_step' => MonthlyAssignmentDossier::STEP_DRAFT,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'completed_at' => null,
            ]);

            app(InternalNotificationService::class)->notifyMonthlyAssignmentDossierReopenedForHoliday(
                $dossier->fresh(['submittedBy']),
                $actor,
                $holiday->name
            );
        }

        return $dossiers;
    }

    /**
     * @param array{
     *     cancelled_count:int,
     *     already_taught: Collection<int, ScheduleSlot>,
     *     reopened_batches: Collection<int, DepartmentMonthlyAssignmentBatch>,
     *     reopened_dossiers: Collection<int, MonthlyAssignmentDossier>,
     * } $result
     */
    private function buildSummaryMessage(HolidayCalendar $holiday, array $result): string
    {
        if ($result['cancelled_count'] === 0 && $result['already_taught']->isEmpty()) {
            return 'Khong co tiet mon hoc nao trung ngay nghi "' . $holiday->name . '".';
        }

        $message = 'Da huy ' . $result['cancelled_count'] . ' tiet trung ngay nghi "' . $holiday->name . '".';

        if ($result['already_taught']->isNotEmpty()) {
            $message .= ' Co ' . $result['already_taught']->count() . ' tiet da diem danh/da day nen khong the huy, vui long xu ly rieng.';
        }

        if ($result['reopened_batches']->isNotEmpty()) {
            $message .= ' Da mo lai ' . $result['reopened_batches']->count() . ' batch phan cong thang de cac Khoa phan cong lai va gui duyet.';
        }

        if ($result['reopened_dossiers']->isNotEmpty()) {
            $message .= ' Da mo lai ' . $result['reopened_dossiers']->count() . ' ho so phan cong thang da gui PDT/BGH.';
        }

        return $message;
    }
}
