<?php

namespace Modules\Schedule\Application\UpdateSchedule;

use Modules\Schedule\Models\Plans;
use Exception;

class UpdateScheduleHandler
{
    /**
     * Handle the update schedule request.
     */
    public function handle(UpdateScheduleRequest $request, $id)
    {
        try {
            $schedule = Plans::findOrFail($id);
            $validated = $request->validated();

            // Check if another schedule exists with the same semester and year
            $duplicateSchedule = Plans::where('semester', $validated['semester'])
                ->where('year', $validated['year'])
                ->where('id', '!=', $id)
                ->first();

            if ($duplicateSchedule) {
                return back()
                    ->withInput()
                    ->with('error', 'Lịch học cho kỳ ' . $validated['semester'] . ' năm ' . $validated['year'] . ' đã tồn tại.');
            }

            // Update schedule
            $schedule->update([
                'semester' => $validated['semester'],
                'year' => $validated['year'],
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()->route('schedule.show', $id)
                ->with('success', 'Lịch học đã được cập nhật thành công.');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Có lỗi khi cập nhật lịch học: ' . $e->getMessage());
        }
    }
}
