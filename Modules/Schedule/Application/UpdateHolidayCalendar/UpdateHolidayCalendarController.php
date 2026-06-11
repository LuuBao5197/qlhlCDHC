<?php

namespace Modules\Schedule\Application\UpdateHolidayCalendar;

use App\Http\Controllers\Controller;

class UpdateHolidayCalendarController extends Controller
{
    public function __construct(
        private UpdateHolidayCalendarHandler $handler
    ) {}

    /**
     * Update a holiday calendar row.
     */
    public function __invoke(UpdateHolidayCalendarRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
