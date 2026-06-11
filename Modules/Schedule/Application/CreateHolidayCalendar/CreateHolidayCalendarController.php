<?php

namespace Modules\Schedule\Application\CreateHolidayCalendar;

use App\Http\Controllers\Controller;

class CreateHolidayCalendarController extends Controller
{
    public function __construct(
        private CreateHolidayCalendarHandler $handler
    ) {}

    /**
     * Store a holiday calendar row.
     */
    public function __invoke(CreateHolidayCalendarRequest $request)
    {
        return $this->handler->handle($request);
    }
}
