<?php

namespace Modules\Schedule\Application\TeachingSupportChangeRequest;

use App\Models\User;
use Modules\Schedule\Models\TeachingSupportChangeRequest;
use Modules\Schedule\Models\TeachingSupportRequest;

class TeachingSupportChangeRequestPolicy
{
    public function view(User $user, TeachingSupportChangeRequest $changeRequest): bool
    {
        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        return $user->isDepartmentStaff()
            && (
                (int) $changeRequest->requesting_department_id === (int) $user->department_id
                || (int) $changeRequest->assigned_supporting_department_id === (int) $user->department_id
                || (int) $changeRequest->proposed_supporting_department_id === (int) $user->department_id
            );
    }

    public function create(User $user, TeachingSupportRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDepartmentStaff()
            && (int) $request->requesting_department_id === (int) $user->department_id
            && in_array($request->status, [
                TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                TeachingSupportRequest::STATUS_COMPLETED,
            ], true);
    }

    public function process(User $user, TeachingSupportChangeRequest $changeRequest): bool
    {
        return $user->isAdmin() || $user->isTrainingOffice();
    }

    public function withdraw(User $user, TeachingSupportRequest $request): bool
    {
        return $user->isDepartmentStaff()
            && (int) $request->requesting_department_id === (int) $user->department_id
            && $request->status === TeachingSupportRequest::STATUS_PENDING_PDT;
    }

}
