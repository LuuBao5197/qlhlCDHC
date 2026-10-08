<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use Modules\Schedule\Models\Plans;

/**
 * Who may open/save the semester plan edit form. Mirrors the "Sửa" button
 * condition on the plan list: only drafts, plus admin back-filled plans.
 */
class PlanEditGuard
{
    public const DENIED_MESSAGE = 'Chỉ được sửa kế hoạch học kỳ khi còn ở trạng thái nháp (chưa gửi duyệt).';

    public static function canEdit(Plans $plan, mixed $user): bool
    {
        if ($user === null || !($user->isTrainingOffice() || $user->isAdmin())) {
            return false;
        }

        if ($plan->status === 'draft') {
            return true;
        }

        return $user->isAdmin() && $plan->adminBackfillLog !== null;
    }
}
