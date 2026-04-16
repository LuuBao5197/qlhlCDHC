<?php

namespace Modules\Schedule\Application\DeleteScheduleSemester;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;

class DeleteScheduleSemesterController extends Controller
{
    public function __construct(
        private DeleteScheduleSemesterHandler $handler
    ) {}

    /**
     * Remove the specified resource from storage.
     */
    public function __invoke(DeleteScheduleSemesterRequest $request, $id)
    {
        return $this->handler->handle($request, $id);
    }
}
