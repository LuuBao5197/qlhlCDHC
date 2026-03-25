<?php

namespace Modules\Schedule\Application\GetScheduleSemester;

use Carbon\Carbon;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;

class GetScheduleSemesterHandler
{
    /**
     * Handle the get semester schedule request.
     */
    public function handle(GetScheduleSemesterRequest $request)
    {
        $validated = $request->validated();

        $semester = $validated['semester'];
        $year = $validated['year'];
        $className = $validated['className'];

        $plan = Plans::where('semester', $semester)
            ->where('year', $year)
            ->first();

        if (!$plan) {
            return view('schedule::semester', [
                'plan' => null,
                'scheduleCalendar' => [],
                'dates' => [],
                'className' => $className
            ]);
        }

        // Get all monthly schedules for this plan and class
        $monthlySchedules = MonthlySchedule::where('plan_id', $plan->id)
            ->where('class_name', $className)
            ->with('scheduleSlots')
            ->orderBy('month')
            ->get();

        // Build calendar data
        $scheduleCalendar = [];
        $allDates = [];

        foreach ($monthlySchedules as $monthly) {
            foreach ($monthly->scheduleSlots as $slot) {
                $dateStr = Carbon::parse($slot->date)->format('Y-m-d');
                $periodNum = $slot->period_number ?? $this->getPeriodNumber($slot->period);

                if (!isset($scheduleCalendar[$dateStr])) {
                    $scheduleCalendar[$dateStr] = [];
                    $allDates[$dateStr] = Carbon::parse($slot->date);
                }

                $scheduleCalendar[$dateStr][$periodNum] = [
                    'subject' => $slot->subject,
                    'content' => $slot->content
                ];
            }
        }

        // Sort dates
        ksort($scheduleCalendar);
        ksort($allDates);

        return view('schedule::semester', [
            'plan' => $plan,
            'scheduleCalendar' => $scheduleCalendar,
            'dates' => $allDates,
            'className' => $className
        ]);
    }

    /**
     * Get period number from period name (for backward compatibility).
     */
    private function getPeriodNumber($periodName)
    {
        if ($periodName === 'Sáng') {
            return rand(1, 5);
        } else if ($periodName === 'Chiều') {
            return rand(6, 9);
        }
        return 1;
    }
}
