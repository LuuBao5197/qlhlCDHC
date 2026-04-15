<?php

namespace Modules\Schedule\Application\ReviewMonthlySchedule;

use App\Http\Controllers\Controller;

class ReviewMonthlyScheduleController extends Controller
{
    public function __construct(
        private ReviewMonthlyScheduleHandler $handler
    ) {}

    /**
     * Review a monthly schedule from department staff.
     */
    public function __invoke(ReviewMonthlyScheduleRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
