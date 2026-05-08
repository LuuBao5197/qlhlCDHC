<?php

namespace Modules\Training\Application\TeacherEvaluation\GetTeacherDailyLog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetTeacherDailyLogController extends Controller
{
    public function __construct(
        private GetTeacherDailyLogHandler $handler
    ) {}

    public function __invoke(Request $request)
    {
        return $this->handler->handle($request, 'view');
    }
}
