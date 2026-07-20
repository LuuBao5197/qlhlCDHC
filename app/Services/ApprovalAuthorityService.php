<?php

namespace App\Services;

use App\Enums\Position;
use App\Models\User;

/**
 * Nơi tập trung duy nhất xác định "Role + Position nào được phép thực hiện bước duyệt nào".
 * Mọi FormRequest/Policy/Controller cần kiểm tra quyền duyệt phải gọi qua đây, không tự
 * kiểm tra role/position rải rác để tránh phân mảnh logic nghiệp vụ.
 */
class ApprovalAuthorityService
{
    private const TRAINING_OFFICE_APPROVER_POSITIONS = [
        Position::TRAINING_HEAD,
        Position::TRAINING_DEPUTY_HEAD,
    ];

    private const LEADERSHIP_APPROVER_POSITIONS = [
        Position::VICE_PRINCIPAL,
        Position::PRINCIPAL,
    ];

    /**
     * Bước "Phòng Đào tạo duyệt" — chỉ Trưởng phòng hoặc Phó trưởng phòng đào tạo, Training Staff không đủ thẩm quyền.
     */
    public function canApproveAsTrainingOffice(?User $user): bool
    {
        return $this->canApprove($user, User::ROLE_TRAINING_OFFICE, self::TRAINING_OFFICE_APPROVER_POSITIONS);
    }

    /**
     * Bước "Ban Giám hiệu duyệt" — Phó hiệu trưởng hoặc Hiệu trưởng.
     */
    public function canApproveAsLeadership(?User $user): bool
    {
        return $this->canApprove($user, User::ROLE_LEADERSHIP, self::LEADERSHIP_APPROVER_POSITIONS);
    }

    /**
     * @param array<int, Position> $allowedPositions
     */
    private function canApprove(?User $user, string $requiredRole, array $allowedPositions): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isRole($requiredRole)) {
            return false;
        }

        return $user->position !== null && in_array($user->position, $allowedPositions, true);
    }
}
