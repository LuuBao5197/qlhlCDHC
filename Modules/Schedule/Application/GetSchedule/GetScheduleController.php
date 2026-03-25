<?php

namespace Modules\Schedule\Application\GetSchedule;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;

class GetScheduleController extends Controller
{
    public function __construct(
        private GetScheduleHandler $handler
    ) {}

    /**
     * Display a listing of schedules.
     */
    public function __invoke(GetScheduleRequest $request)
    {
        return $this->handler->handle($request);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $schedule = Plans::findOrFail($id);
        return view('schedule::show', ['schedule' => $schedule]);
    }
}
