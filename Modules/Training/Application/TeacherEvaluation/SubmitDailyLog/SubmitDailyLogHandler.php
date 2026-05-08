<?php

namespace Modules\Training\Application\TeacherEvaluation\SubmitDailyLog;

use Carbon\Carbon;
use Modules\Training\Models\TeacherDailySummary;

class SubmitDailyLogHandler
{
    public function handle(SubmitDailyLogRequest $request, int $dutyOfficerId): void
    {
        $validated = $request->validated();
        $date = Carbon::parse($validated['date'])->toDateString();

        // Chỉ lưu nhận xét tổng hợp của trực ban (Phần 2 của nhật ký).
        // Phần 1 chỉ hiển thị dữ liệu và không cho chỉnh sửa từ màn hình trực ban.
        TeacherDailySummary::updateOrCreate(
            [
                'teacher_id' => $dutyOfficerId, // teacher_id ở đây là ID của trực ban
                'log_date'   => $date,
            ],
            [
                'training_plan_comment' => $validated['training_plan_comment'] ?? null,
                'regulation_comment'    => $validated['regulation_comment'] ?? null,
                'facility_comment'      => $validated['facility_comment'] ?? null,
                'followup_comment'      => $validated['followup_comment'] ?? null,
            ]
        );
    }
}
