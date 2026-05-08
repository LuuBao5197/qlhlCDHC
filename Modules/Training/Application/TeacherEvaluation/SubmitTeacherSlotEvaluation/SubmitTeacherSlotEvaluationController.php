<?php

namespace Modules\Training\Application\TeacherEvaluation\SubmitTeacherSlotEvaluation;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SubmitTeacherSlotEvaluationController extends Controller
{
    public function __construct(
        private SubmitTeacherSlotEvaluationHandler $handler
    ) {}

    public function __invoke(SubmitTeacherSlotEvaluationRequest $request): RedirectResponse
    {
        $this->handler->handle($request, (int) $request->user()->id);

        return redirect()
            ->route('teacher-slot-evaluations.index', ['date' => $request->input('date')])
            ->with('success', 'Đánh giá tiết học đã được lưu thành công.');
    }
}
