<?php

namespace Modules\Schedule\Application\CancelSlotsForHoliday;

use App\Http\Controllers\Controller;

class CancelSlotsForHolidayController extends Controller
{
    public function __construct(
        private CancelSlotsForHolidayHandler $handler
    ) {}

    /**
     * Cancel every not-yet-taught subject slot that lands on the given holiday's date.
     */
    public function __invoke(CancelSlotsForHolidayRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
