<?php

namespace Modules\Training\Application\Management\Shared;

use Modules\Schedule\Models\Plans;

/**
 * A training_batch is "locked" for class membership changes once it has a semester
 * plan that is no longer a free-form draft (submitted for review or already
 * approved). Adding/moving a class into a locked batch after that point would
 * silently create a class with no plan_template/semester_event coverage, since
 * nobody will revisit and resubmit the plan just because a class was added later.
 */
class TrainingBatchPlanLockChecker
{
    private const LOCKED_STATUSES = ['submitted', 'approved'];

    public function isLocked(?int $trainingBatchId): bool
    {
        if ($trainingBatchId === null) {
            return false;
        }

        return Plans::query()
            ->where('training_batch_id', $trainingBatchId)
            ->whereIn('status', self::LOCKED_STATUSES)
            ->exists();
    }
}
