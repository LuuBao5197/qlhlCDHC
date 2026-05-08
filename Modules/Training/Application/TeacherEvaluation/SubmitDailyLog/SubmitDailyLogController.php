<?php

namespace Modules\Training\Application\TeacherEvaluation\SubmitDailyLog;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SubmitDailyLogController extends Controller
{
    public function __construct(
        private SubmitDailyLogHandler $handler
    ) {}

    public function __invoke(SubmitDailyLogRequest $request): RedirectResponse
    {
        try {
            $this->handler->handle($request, $request->user()->id);

            return redirect()
                ->route('duty-log.edit', ['date' => $request->input('date')])
                ->with('success', 'Nhật ký trực ban huấn luyện đã được lưu thành công.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
