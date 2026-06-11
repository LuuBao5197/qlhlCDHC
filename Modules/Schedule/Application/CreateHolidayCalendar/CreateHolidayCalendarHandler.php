<?php

namespace Modules\Schedule\Application\CreateHolidayCalendar;

use Modules\Training\Models\HolidayCalendar;
use Throwable;

class CreateHolidayCalendarHandler
{
    public function handle(CreateHolidayCalendarRequest $request)
    {
        $validated = $request->validated();

        try {
            HolidayCalendar::query()->create([
                'name' => (string) $validated['name'],
                'date' => (string) $validated['date'],
                'note' => $validated['note'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Khong the tao ngay nghi: ' . $exception->getMessage());
        }

        return back()->with('success', 'Da them ngay nghi vao holiday calendar.');
    }
}
