<?php

namespace Modules\Schedule\Application\DeleteHolidayCalendar;

use Modules\Training\Models\HolidayCalendar;
use Throwable;

class DeleteHolidayCalendarHandler
{
    public function handle(DeleteHolidayCalendarRequest $request, int $id)
    {
        $holiday = HolidayCalendar::query()->findOrFail($id);

        try {
            $holiday->delete();
        } catch (Throwable $exception) {
            return back()->with('error', 'Khong the xoa ngay nghi: ' . $exception->getMessage());
        }

        return back()->with('success', 'Da xoa ngay nghi khoi holiday calendar.');
    }
}
