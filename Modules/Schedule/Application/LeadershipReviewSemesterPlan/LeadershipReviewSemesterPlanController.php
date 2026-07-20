<?php

namespace Modules\Schedule\Application\LeadershipReviewSemesterPlan;

use App\Http\Controllers\Controller;

class LeadershipReviewSemesterPlanController extends Controller
{
    public function __construct(
        private LeadershipReviewSemesterPlanHandler $handler
    ) {}

    /**
     * Handle leadership (Ban Giam hieu) review of a submitted semester plan.
     */
    public function __invoke(LeadershipReviewSemesterPlanRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
