<?php

namespace Modules\Schedule\Application\SubmitSemesterPlan;

use App\Http\Controllers\Controller;

class SubmitSemesterPlanController extends Controller
{
    public function __construct(
        private SubmitSemesterPlanHandler $handler
    ) {}

    /**
     * Submit a semester plan for leadership review.
     */
    public function __invoke(SubmitSemesterPlanRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
