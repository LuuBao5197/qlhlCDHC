<?php

namespace Modules\Schedule\Application\GetSchedule;

use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\ChangeRequest;

class GetScheduleHandler
{
    /**
     * Handle the get schedules request.
     */
    public function handle(GetScheduleRequest $request)
    {
        // Get all schedules with pagination
        $perPage = $request->input('per_page', 15);
        $schedules = Plans::query()
            ->with(['createdBy', 'submittedBy'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $monthlySchedules = MonthlySchedule::query()
            ->with(['plan', 'department', 'trainingClass', 'createdBy', 'approvedBy'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $changeRequests = ChangeRequest::query()
            ->with(['monthlySchedule.plan', 'scheduleSlot', 'requestedBy'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('schedule::index', [
            'schedules' => $schedules,
            'monthlySchedules' => $monthlySchedules,
            'changeRequests' => $changeRequests,
        ]);
    }
}
