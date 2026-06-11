<?php

namespace Modules\Schedule\Application\DeleteHolidayCalendar;

use App\Http\Controllers\Controller;

class DeleteHolidayCalendarController extends Controller
{
    public function __construct(
        private DeleteHolidayCalendarHandler $handler
    ) {}

    /**
     * Delete a holiday calendar row.
     */
    public function __invoke(DeleteHolidayCalendarRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
