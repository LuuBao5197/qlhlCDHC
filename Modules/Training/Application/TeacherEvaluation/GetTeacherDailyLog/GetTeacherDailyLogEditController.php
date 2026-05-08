<?php

namespace Modules\Training\Application\TeacherEvaluation\GetTeacherDailyLog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetTeacherDailyLogEditController extends Controller
{
    public function __construct(
        private GetTeacherDailyLogHandler $handler
    ) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->isTrainingOffice(),
            403
        );

        return $this->handler->handle($request, 'edit');
    }
}
