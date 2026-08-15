<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use App\Http\Controllers\Controller;

class PreviewScheduleSemesterController extends Controller
{
    public function __construct(
        private PreviewScheduleSemesterHandler $handler
    ) {}

    public function __invoke(PreviewScheduleSemesterRequest $request)
    {
        return response()->json($this->handler->handle($request));
    }
}
