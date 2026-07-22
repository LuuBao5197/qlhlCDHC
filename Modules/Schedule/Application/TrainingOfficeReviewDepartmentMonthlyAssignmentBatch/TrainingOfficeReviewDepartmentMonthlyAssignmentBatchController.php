<?php

namespace Modules\Schedule\Application\TrainingOfficeReviewDepartmentMonthlyAssignmentBatch;

use App\Http\Controllers\Controller;

class TrainingOfficeReviewDepartmentMonthlyAssignmentBatchController extends Controller
{
    public function __construct(
        private TrainingOfficeReviewDepartmentMonthlyAssignmentBatchHandler $handler
    ) {}

    /**
     * Handle PDT review of a department monthly assignment batch already approved by department leadership.
     */
    public function __invoke(TrainingOfficeReviewDepartmentMonthlyAssignmentBatchRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
