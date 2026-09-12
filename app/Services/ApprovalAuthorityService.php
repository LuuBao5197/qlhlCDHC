<?php

namespace App\Services;

use App\Models\User;

/**
 * Nơi tập trung duy nhất xác định "Role nào được phép thực hiện bước duyệt nào".
 * Mọi FormRequest/Policy/Controller cần kiểm tra quyền duyệt phải gọi qua đây, không tự
 * kiểm tra role rải rác để tránh phân mảnh logic nghiệp vụ.
 */
class ApprovalAuthorityService
{
    /**
     * Bước "Phòng Đào tạo duyệt" — chỉ Trưởng/Phó phòng đào tạo (role training_office_head).
     */
    public function canApproveAsTrainingOffice(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin() || $user->isTrainingOfficeHead();
    }

    /**
     * Bước "Ban Giám hiệu duyệt" — mọi thành viên role leadership.
     */
    public function canApproveAsLeadership(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin() || $user->isLeadership();
    }

    /**
     * Bước "Lãnh đạo Khoa duyệt" — chỉ Chủ nhiệm khoa (role department_head) của đúng Khoa đang xét.
     */
    public function canApproveAsDepartmentLeadership(?User $user, ?int $departmentId): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($departmentId === null || (int) $departmentId <= 0) {
            return false;
        }

        if (! $user->isDepartmentHead()) {
            return false;
        }

        return (int) $user->department_id === (int) $departmentId;
    }
}
