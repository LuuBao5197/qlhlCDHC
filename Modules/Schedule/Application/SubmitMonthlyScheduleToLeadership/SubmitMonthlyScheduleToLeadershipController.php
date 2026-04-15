<?php

namespace Modules\Schedule\Application\SubmitMonthlyScheduleToLeadership;

use App\Http\Controllers\Controller;

class SubmitMonthlyScheduleToLeadershipController extends Controller
{
    public function __construct(
        private SubmitMonthlyScheduleToLeadershipHandler $handler
    ) {}

    /**
     * Submit an approved monthly schedule to leadership.
     */
    public function __invoke(SubmitMonthlyScheduleToLeadershipRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
