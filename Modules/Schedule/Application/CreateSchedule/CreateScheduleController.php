<?php

namespace Modules\Schedule\Application\CreateSchedule;

use App\Http\Controllers\Controller;

class CreateScheduleController extends Controller
{
    public function __construct(
        private CreateScheduleHandler $handler
    ) {}

    /**
     * Show the form for creating a new resource.
     */
    public function showForm()
    {
        return view('schedule::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function __invoke(CreateScheduleRequest $request)
    {
        return $this->handler->handle($request);
    }
}
