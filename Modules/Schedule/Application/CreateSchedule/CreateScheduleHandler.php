<?php

namespace Modules\Schedule\Application\CreateSchedule;

use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Exception;
use Carbon\Carbon;

class CreateScheduleHandler
{
    /**
     * Handle the create schedule request.
     */
    public function handle(CreateScheduleRequest $request)
    {
        try {
            $validated = $request->validated();

            // Check if schedule already exists for this class and date range
            $existingSchedule = Plans::where('name', $validated['class_name'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('created_at', [
                        Carbon::parse($validated['start_date'])->startOfDay(),
                        Carbon::parse($validated['end_date'])->endOfDay()
                    ]);
                })
                ->first();

            if ($existingSchedule) {
                return back()
                    ->withInput()
                    ->with('error', 'Lịch học cho lớp ' . $validated['class_name'] . ' trong khoảng thời gian này đã tồn tại.');
            }

            // Create new schedule
            $schedule = Plans::create([
                'name' => $validated['class_name'],
                'description' => $validated['description'] ?? null,
            ]);

            // Create monthly schedules and slots for the date range
            $this->createSchedulesForDateRange($schedule, $validated['start_date'], $validated['end_date'], $validated['class_name']);

            return redirect()->route('schedule.show', $schedule->id)
                ->with('success', 'Lịch học đã được tạo thành công với các tháng và buổi học tương ứng.');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Có lỗi khi tạo lịch học: ' . $e->getMessage());
        }
    }

    /**
     * Create monthly schedules and schedule slots for the given date range.
     */
    private function createSchedulesForDateRange(Plans $schedule, string $startDate, string $endDate, string $className)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $currentMonth = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();

        while ($currentMonth <= $endMonth) {
            // Create MonthlySchedule for this month
            $monthlySchedule = MonthlySchedule::create([
                'plan_id' => $schedule->id,
                'class_name' => $className,
                'month' => $currentMonth->month,
                'year' => $currentMonth->year,
            ]);

            // Create ScheduleSlots for this month
            $this->createScheduleSlotsForMonth($monthlySchedule, $currentMonth->year, $currentMonth->month, $startDate, $endDate);

            $currentMonth->addMonth();
        }
    }

    /**
     * Create schedule slots for a given month within the date range.
     */
    private function createScheduleSlotsForMonth(MonthlySchedule $monthlySchedule, int $year, int $month, string $startDate, string $endDate)
    {
        $monthStart = Carbon::create($year, $month, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Adjust for the actual date range
        $effectiveStart = max($monthStart, Carbon::parse($startDate));
        $effectiveEnd = min($monthEnd, Carbon::parse($endDate));

        $currentDate = $effectiveStart->copy();

        while ($currentDate <= $effectiveEnd) {
            // Skip Saturdays (6) and Sundays (0) - Vietnamese weekend
            if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY) {
                // Create 9 period slots per day (1-9)
                for ($period = 1; $period <= 9; $period++) {
                    ScheduleSlot::create([
                        'monthly_schedule_id' => $monthlySchedule->id,
                        'date' => $currentDate->toDateString(),
                        'day_of_week' => $currentDate->dayOfWeek,
                        'period' => $this->getPeriodName($period),
                        'period_number' => $period,
                        'subject' => null, // To be updated later
                        'content' => null, // To be updated later
                    ]);
                }
            }

            $currentDate->addDay();
        }
    }

    /**
     * Get period name based on period number.
     */
    private function getPeriodName(int $period): string
    {
        $periodNames = [
            1 => 'Sáng 1',
            2 => 'Sáng 2',
            3 => 'Sáng 3',
            4 => 'Sáng 4',
            5 => 'Sáng 5',
            6 => 'Chiều 1',
            7 => 'Chiều 2',
            8 => 'Chiều 3',
            9 => 'Chiều 4',
        ];

        return $periodNames[$period] ?? 'Buổi ' . $period;
    }
}
