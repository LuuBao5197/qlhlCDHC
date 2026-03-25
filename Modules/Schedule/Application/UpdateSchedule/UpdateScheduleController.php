<?php

namespace Modules\Schedule\Application\UpdateSchedule;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;

class UpdateScheduleController extends Controller
{
    public function __construct(
        private UpdateScheduleHandler $handler
    ) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function showForm($id)
    {
        $schedule = Plans::findOrFail($id);
        return view('schedule::edit', ['schedule' => $schedule]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function __invoke(UpdateScheduleRequest $request, $id)
    {
        return $this->handler->handle($request, $id);
    }
}
