<?php

namespace Modules\Schedule\Application\TrainingOfficeReviewSemesterPlan;

use App\Http\Controllers\Controller;

class TrainingOfficeReviewSemesterPlanController extends Controller
{
    public function __construct(
        private TrainingOfficeReviewSemesterPlanHandler $handler
    ) {}

    /**
     * Handle Phong Dao tao leadership review of a semester plan submitted by staff.
     */
    public function __invoke(TrainingOfficeReviewSemesterPlanRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
