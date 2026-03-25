<?php

namespace Modules\Schedule\Application\GetSchedule;

use Modules\Schedule\Models\Plans;

class GetScheduleHandler
{
    /**
     * Handle the get schedules request.
     */
    public function handle(GetScheduleRequest $request)
    {
        // Get all schedules with pagination
        $perPage = $request->input('per_page', 15);
        $schedules = Plans::paginate($perPage);

        return view('schedule::index', [
            'schedules' => $schedules
        ]);
    }
}
