<?php

namespace Modules\Training\Application\TeacherEvaluation\GetTeacherDailyLog;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\DailyTrainingLog;
use Modules\Training\Models\SlotEvaluation;
use Modules\Training\Models\TeacherDailySummary;

class GetTeacherDailyLogHandler
{
    public function handle(Request $request, string $mode = 'view')
    {
        $user = $request->user();

        abort_unless($user !== null && ($user->isTrainingOffice() || $user->isAdmin()), 403);

        // Default to today if no date provided
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $dateCarbon = Carbon::parse($date);
        $isToday = $dateCarbon->isSameDay(Carbon::today());
        $isTestMode = app()->environment(['local', 'testing']);
        $adminBackfillEligible = $user->isAdmin();
        $adminBackfillMode = $adminBackfillEligible && $request->boolean('admin_backfill');
        $canEditDate = $isToday || $isTestMode || $adminBackfillMode;
        $isEditMode = $mode === 'edit';

        // Fetch ALL schedule slots happening on the given date (all classes, all teachers)
        // ordered by class then period for easy reading
        $slots = ScheduleSlot::with(['trainingClass', 'room', 'subjectModel', 'subjectLesson', 'teacher'])
            ->whereDate('date', $date)
            ->orderBy('class_id')
            ->orderBy('period_number')
            ->get();

        // Fetch existing DailyTrainingLog records for these slots
        $slotIds = $slots->pluck('id');
        $logs = DailyTrainingLog::whereIn('schedule_slot_id', $slotIds)
            ->get()
            ->keyBy('schedule_slot_id');

        // Prefer teacher slot evaluations for QS/V/Nhan xet in section 1
        $slotEvaluations = SlotEvaluation::query()
            ->whereIn('schedule_slot_id', $slotIds)
            ->orderByDesc('updated_at')
            ->get()
            ->keyBy('schedule_slot_id');

        // Fetch the shared training-office summary for this date.
        $dailySummary = TeacherDailySummary::where('log_date', $date)
            ->first();

        $viewName = $isEditMode
            ? 'training::teacher-evaluation.daily-log-edit'
            : 'training::teacher-evaluation.daily-log';

        return view($viewName, [
            'dutyOfficer'  => $user,
            'date'         => $dateCarbon,
            'slots'        => $slots,
            'logs'         => $logs,
            'slotEvaluations' => $slotEvaluations,
            'dailySummary' => $dailySummary,
            'isEditMode'   => $isEditMode,
            'canEditDate'  => $canEditDate,
            'isToday'      => $isToday,
            'isTestMode'   => $isTestMode,
            'adminBackfillEligible' => $adminBackfillEligible,
            'adminBackfillMode' => $adminBackfillMode,
        ]);
    }
}
