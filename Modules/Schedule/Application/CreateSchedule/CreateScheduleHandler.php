<?php

namespace Modules\Schedule\Application\CreateSchedule;

use Modules\Schedule\Models\Plans;
use Exception;

class CreateScheduleHandler
{
    /**
     * Handle the create schedule request.
     */
    public function handle(CreateScheduleRequest $request)
    {
        try {
            $validated = $request->validated();

            // Check if schedule already exists for this semester and year
            $existingSchedule = Plans::where('semester', $validated['semester'])
                ->where('year', $validated['year'])
                ->first();

            if ($existingSchedule) {
                return back()
                    ->withInput()
                    ->with('error', 'Lịch học cho kỳ ' . $validated['semester'] . ' năm ' . $validated['year'] . ' đã tồn tại.');
            }

            // Create new schedule
            $schedule = Plans::create([
                'semester' => $validated['semester'],
                'year' => $validated['year'],
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()->route('schedule.show', $schedule->id)
                ->with('success', 'Lịch học đã được tạo thành công.');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Có lỗi khi tạo lịch học: ' . $e->getMessage());
        }
    }
}
