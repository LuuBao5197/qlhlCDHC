<?php

namespace Modules\Schedule\Application\MonthlyAssignmentDossier;

use App\Models\User;
use Modules\Schedule\Models\MonthlyAssignmentDossier;

class MonthlyAssignmentDossierPolicy
{
    /**
     * Xem hop cho / danh sach ho so tong hop: PDT (tao/gui), Lanh dao PDT va BGH (duyet), admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainingOffice() || $user->isLeadership();
    }

    public function view(User $user, MonthlyAssignmentDossier $dossier): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Tao/lam moi ban nhap va gui ho so tong hop: nhan vien Phong Dao tao (bat ky position) hoac admin.
     */
    public function build(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainingOffice();
    }
}
