<?php

namespace Modules\Schedule\Application\ViewScheduleCalendar;

use App\Models\Role;
use App\Models\User;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\TrainingClass;

/**
 * Quyen xem lich: toan truong (admin, PDT, BGH), theo khoa (lanh dao/giao vu khoa, giao vien),
 * lich rieng cua giao vien, lich lop cua hoc vien.
 */
class ScheduleCalendarAccess
{
    public const SCOPE_SCHOOL = 'school';

    public const SCOPE_DEPARTMENT = 'department';

    public const SCOPE_CLASS = 'class';

    public const SCOPE_MINE = 'mine';

    public const SCOPES = [self::SCOPE_SCHOOL, self::SCOPE_DEPARTMENT, self::SCOPE_CLASS, self::SCOPE_MINE];

    public function canViewSchool(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN,
            Role::TRAINING_OFFICE,
            Role::TRAINING_OFFICE_HEAD,
            Role::LEADERSHIP,
        ]);
    }

    /**
     * Cac khoa user duoc xem (rong neu khong co quyen theo khoa). Khong dung cho quyen toan truong.
     *
     * @return array<int, int>
     */
    public function departmentIds(User $user): array
    {
        $ids = [];

        if ($user->hasAnyRole([Role::DEPARTMENT_STAFF, Role::DEPARTMENT_HEAD]) && $user->department_id !== null) {
            $ids[] = (int) $user->department_id;
        }

        if ($user->hasRole(Role::TEACHER)) {
            $teacherDepartmentId = $user->teacher?->department_id;
            if ($teacherDepartmentId !== null) {
                $ids[] = (int) $teacherDepartmentId;
            }
        }

        return array_values(array_unique($ids));
    }

    public function teacherId(User $user): ?int
    {
        if (! $user->hasRole(Role::TEACHER)) {
            return null;
        }

        $id = $user->teacher?->id;

        return $id !== null ? (int) $id : null;
    }

    /**
     * Lop cua tai khoan lop (role class_account): chi duoc xem lich lop duoc gan cho tai khoan do.
     *
     * @return array<int, int>
     */
    public function ownClassIds(User $user): array
    {
        if (! $user->hasRole(Role::CLASS_ACCOUNT)) {
            return [];
        }

        return TrainingClass::query()
            ->where('account_user_id', $user->id)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public function canView(User $user, string $scope, ?int $id): bool
    {
        if ($this->canViewSchool($user)) {
            return true;
        }

        return match ($scope) {
            self::SCOPE_SCHOOL => false,
            self::SCOPE_DEPARTMENT => $id !== null && in_array($id, $this->departmentIds($user), true),
            self::SCOPE_MINE => $this->teacherId($user) !== null,
            self::SCOPE_CLASS => $id !== null && $this->canViewClass($user, $id),
            default => false,
        };
    }

    private function canViewClass(User $user, int $classId): bool
    {
        if (in_array($classId, $this->ownClassIds($user), true)) {
            return true;
        }

        $departmentIds = $this->departmentIds($user);
        if ($departmentIds === []) {
            return false;
        }

        // Lop khong co department_id; lop thuoc khoa khi co tiet cua mon thuoc khoa do.
        return ScheduleSlot::query()
            ->where('class_id', $classId)
            ->whereHas('subjectModel', fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->exists();
    }

    /**
     * Pham vi mac dinh khi nguoi dung chua chon.
     *
     * @return array{0: string, 1: int|null}
     */
    public function defaultScope(User $user): array
    {
        if ($this->canViewSchool($user)) {
            return [self::SCOPE_SCHOOL, null];
        }

        $departmentIds = $this->departmentIds($user);
        if ($departmentIds !== []) {
            return [self::SCOPE_DEPARTMENT, $departmentIds[0]];
        }

        $classIds = $this->ownClassIds($user);
        if ($classIds !== []) {
            return [self::SCOPE_CLASS, $classIds[0]];
        }

        if ($this->teacherId($user) !== null) {
            return [self::SCOPE_MINE, null];
        }

        return [self::SCOPE_SCHOOL, null];
    }
}
