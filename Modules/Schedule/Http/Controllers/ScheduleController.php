<?php

namespace Modules\Schedule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('schedule::index');
    }

    /**
     * Display semester schedule.
     */
    public function semester($semester = 1, $year = 2026, $className = 'CD01')
    {
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
                $dateStr = \Carbon\Carbon::parse($slot->date)->format('Y-m-d');
                $periodNum = $slot->period_number ?? $this->getPeriodNumber($slot->period);

                if (!isset($scheduleCalendar[$dateStr])) {
                    $scheduleCalendar[$dateStr] = [];
                    $allDates[$dateStr] = \Carbon\Carbon::parse($slot->date);
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('schedule::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('schedule::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('schedule::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
