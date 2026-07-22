<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Models\User;
use App\Services\ApprovalAuthorityService;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;

class DepartmentMonthlyAssignmentBatchPolicy
{
    public function __construct(private ApprovalAuthorityService $approvalAuthority) {}

    /**
     * Xem hop cho phe duyet (danh sach batch). Khoa chi thay batch cua khoa minh (loc o controller),
     * PDT/admin thay toan bo (ca 2 hang doi: cho Khoa duyet + cho PDT duyet).
     */
    public function viewQueue(User $user): bool
    {
        return $user->isAdmin() || $user->isTrainingOffice() || $user->isDepartmentStaff();
    }

    public function view(User $user, DepartmentMonthlyAssignmentBatch $batch): bool
    {
        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        return $user->isDepartmentStaff() && (int) $batch->department_id === (int) $user->department_id;
    }

    /**
     * Nhan vien Khoa (dung khoa) hoac admin duoc tao/gui batch len Lanh dao Khoa.
     */
    public function submit(User $user, int $departmentId): bool
    {
        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        return $user->isDepartmentStaff() && (int) $user->department_id === $departmentId;
    }

    /**
     * Buoc "Lanh dao Khoa duyet" — chi Truong khoa/Bo mon cua dung Khoa dang xet.
     */
    public function reviewAsDepartmentLeadership(User $user, DepartmentMonthlyAssignmentBatch $batch): bool
    {
        return $this->approvalAuthority->canApproveAsDepartmentLeadership($user, (int) $batch->department_id);
    }

    /**
     * Buoc "PDT duyet" — Truong/Pho truong phong dao tao, khong phu thuoc Khoa nao.
     */
    public function reviewAsTrainingOffice(User $user): bool
    {
        return $this->approvalAuthority->canApproveAsTrainingOffice($user);
    }
}
