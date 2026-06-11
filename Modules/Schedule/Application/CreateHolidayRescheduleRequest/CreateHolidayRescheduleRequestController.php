<?php

namespace Modules\Schedule\Application\CreateHolidayRescheduleRequest;

use App\Http\Controllers\Controller;

class CreateHolidayRescheduleRequestController extends Controller
{
    public function __construct(
        private CreateHolidayRescheduleRequestHandler $handler
    ) {}

    /**
     * Create a holiday-based bulk reschedule request.
     */
    public function __invoke(CreateHolidayRescheduleRequestRequest $request)
    {
        return $this->handler->handle($request);
    }
}
