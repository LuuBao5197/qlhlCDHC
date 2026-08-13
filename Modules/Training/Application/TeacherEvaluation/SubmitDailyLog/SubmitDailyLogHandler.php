<?php

namespace Modules\Training\Application\TeacherEvaluation\SubmitDailyLog;

use App\Support\AdminBackfillContext;
use Carbon\Carbon;
use Modules\Training\Models\TeacherDailySummary;

class SubmitDailyLogHandler
{
    public function handle(SubmitDailyLogRequest $request, int $dutyOfficerId): void
    {
        $validated = $request->validated();
        $date = Carbon::parse($validated['date'])->toDateString();

        // Chỉ lưu nhận xét tổng hợp theo ngày của phòng đào tạo (Phần 2 của nhật ký).
        // Phần 1 chỉ hiển thị dữ liệu và không cho chỉnh sửa từ màn hình trực ban.
        $summary = TeacherDailySummary::updateOrCreate(
            [
                'log_date' => $date,
            ],
            [
                'teacher_id' => $dutyOfficerId, // teacher_id ở đây là ID của trực ban lưu lần gần nhất
                'training_plan_comment' => $validated['training_plan_comment'] ?? null,
                'regulation_comment'    => $validated['regulation_comment'] ?? null,
                'facility_comment'      => $validated['facility_comment'] ?? null,
                'followup_comment'      => $validated['followup_comment'] ?? null,
            ]
        );

        AdminBackfillContext::log($request, 'teacher_daily_log', $summary);
    }
}
