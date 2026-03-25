<?php

namespace Modules\Schedule\Application\DeleteSchedule;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;

class DeleteScheduleController extends Controller
{
    public function __construct(
        private DeleteScheduleHandler $handler
    ) {}

    /**
     * Remove the specified resource from storage.
     */
    public function __invoke(DeleteScheduleRequest $request, $id)
    {
        return $this->handler->handle($request, $id);
    }
}
