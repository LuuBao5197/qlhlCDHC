<?php

namespace Modules\Schedule\Application\TeachingSupportRequest;

use App\Models\User;
use Modules\Schedule\Models\TeachingSupportRequest;

class TeachingSupportRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainingOffice() || $user->isDepartmentStaff();
    }

    public function view(User $user, TeachingSupportRequest $request): bool
    {
        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        return $user->isDepartmentStaff()
            && (
                (int) $request->requesting_department_id === (int) $user->department_id
                || (int) $request->assigned_supporting_department_id === (int) $user->department_id
                || (int) $request->proposed_supporting_department_id === (int) $user->department_id
            );
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainingOffice() || $user->isDepartmentStaff();
    }

    public function process(User $user, TeachingSupportRequest $request): bool
    {
        return $user->isAdmin() || $user->isTrainingOffice();
    }

    public function confirm(User $user, TeachingSupportRequest $request): bool
    {
        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        return $user->isDepartmentStaff()
            && (int) $request->assigned_supporting_department_id === (int) $user->department_id;
    }

    public function withdraw(User $user, TeachingSupportRequest $request): bool
    {
        return $user->isDepartmentStaff()
            && (int) $request->requesting_department_id === (int) $user->department_id
            && $request->status === TeachingSupportRequest::STATUS_PENDING_PDT;
    }
}
