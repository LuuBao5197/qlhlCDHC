<?php

namespace Modules\Schedule\Application\DeleteSchedule;

use Modules\Schedule\Models\Plans;
use Exception;

class DeleteScheduleHandler
{
    /**
     * Handle the delete schedule request.
     */
    public function handle(DeleteScheduleRequest $request, $id)
    {
        try {
            $schedule = Plans::findOrFail($id);

            // Delete associated monthly schedules and slots
            $schedule->monthlySchedules()->each(function ($monthly) {
                $monthly->scheduleSlots()->delete();
                $monthly->delete();
            });

            // Delete the schedule
            $schedule->delete();

            return redirect()->route('schedule.index')
                ->with('success', 'Lịch học đã được xóa thành công.');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Có lỗi khi xóa lịch học: ' . $e->getMessage());
        }
    }
}
