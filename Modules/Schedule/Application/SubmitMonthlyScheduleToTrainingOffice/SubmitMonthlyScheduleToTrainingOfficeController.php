<?php

namespace Modules\Schedule\Application\SubmitMonthlyScheduleToTrainingOffice;

use App\Http\Controllers\Controller;

class SubmitMonthlyScheduleToTrainingOfficeController extends Controller
{
    public function __construct(
        private SubmitMonthlyScheduleToTrainingOfficeHandler $handler
    ) {}

    /**
     * Submit monthly schedule to training office.
     */
    public function __invoke(SubmitMonthlyScheduleToTrainingOfficeRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
