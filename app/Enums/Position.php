<?php

namespace App\Enums;

use App\Models\User;

enum Position: string
{
    case DEPARTMENT_STAFF = 'department_staff';
    case DEPARTMENT_HEAD = 'department_head';

    case TRAINING_STAFF = 'training_staff';
    case TRAINING_DEPUTY_HEAD = 'training_deputy_head';
    case TRAINING_HEAD = 'training_head';

    case VICE_PRINCIPAL = 'vice_principal';
    case PRINCIPAL = 'principal';

    public function label(): string
    {
        return match ($this) {
            self::DEPARTMENT_STAFF => 'Nhân viên khoa',
            self::DEPARTMENT_HEAD => 'Trưởng khoa/Bộ môn',
            self::TRAINING_STAFF => 'Nhân viên phòng đào tạo',
            self::TRAINING_DEPUTY_HEAD => 'Phó trưởng phòng đào tạo',
            self::TRAINING_HEAD => 'Trưởng phòng đào tạo',
            self::VICE_PRINCIPAL => 'Phó hiệu trưởng',
            self::PRINCIPAL => 'Hiệu trưởng',
        };
    }

    /**
     * Danh sách Position hợp lệ cho 1 role — nguồn duy nhất cho validation và admin UI.
     *
     * @return array<int, self>
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            User::ROLE_DEPARTMENT_STAFF => [self::DEPARTMENT_STAFF, self::DEPARTMENT_HEAD],
            User::ROLE_TRAINING_OFFICE => [self::TRAINING_STAFF, self::TRAINING_DEPUTY_HEAD, self::TRAINING_HEAD],
            User::ROLE_LEADERSHIP => [self::VICE_PRINCIPAL, self::PRINCIPAL],
            default => [],
        };
    }
}
