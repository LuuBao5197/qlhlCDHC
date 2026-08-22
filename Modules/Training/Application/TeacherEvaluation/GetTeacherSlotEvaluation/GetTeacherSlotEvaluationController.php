<?php

namespace Modules\Training\Application\TeacherEvaluation\GetTeacherSlotEvaluation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetTeacherSlotEvaluationController extends Controller
{
    public function __construct(
        private GetTeacherSlotEvaluationHandler $handler
    ) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();

        abort_unless($user !== null && ($user->isTeacher() || $user->isAdmin()), 403);

        return $this->handler->handle($request);
    }
}
