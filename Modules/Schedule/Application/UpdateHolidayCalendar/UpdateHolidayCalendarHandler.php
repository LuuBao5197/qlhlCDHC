<?php

namespace Modules\Schedule\Application\UpdateHolidayCalendar;

use Modules\Training\Models\HolidayCalendar;
use Throwable;

class UpdateHolidayCalendarHandler
{
    public function handle(UpdateHolidayCalendarRequest $request, int $id)
    {
        $validated = $request->validated();
        $holiday = HolidayCalendar::query()->findOrFail($id);

        try {
            $holiday->update([
                'name' => (string) $validated['name'],
                'start_date' => (string) $validated['start_date'],
                'end_date' => (string) $validated['end_date'],
                'note' => $validated['note'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'updated_by' => $request->user()?->id,
            ]);
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Khong the cap nhat ngay nghi: ' . $exception->getMessage());
        }

        return back()->with('success', 'Da cap nhat holiday calendar.');
    }
}
