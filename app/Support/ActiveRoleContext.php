<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

/**
 * "Active role" chỉ quyết định giao diện/menu hiển thị cho tài khoản có nhiều role —
 * KHÔNG phải ranh giới bảo mật. Mọi kiểm tra quyền thao tác (Policy/FormRequest/Service)
 * vẫn dựa trên toàn bộ role mà user đang giữ (xem User::hasRole()/hasAnyRole()), bất kể
 * đang active role nào, để không phá vỡ các link duyệt trong thông báo/email.
 */
class ActiveRoleContext
{
    private const SESSION_KEY = 'active_role_slug';

    /**
     * Thứ tự ưu tiên khi user chưa từng chọn active role (hoặc role đã chọn không còn hợp lệ).
     */
    private const PRIORITY = [
        Role::ADMIN,
        Role::LEADERSHIP,
        Role::TRAINING_OFFICE_HEAD,
        Role::TRAINING_OFFICE,
        Role::DEPARTMENT_HEAD,
        Role::DEPARTMENT_STAFF,
        Role::TEACHER,
        Role::STUDENT,
    ];

    public static function current(User $user): ?Role
    {
        $roles = $user->roles;

        if ($roles->isEmpty()) {
            return null;
        }

        $sessionSlug = session(self::SESSION_KEY);
        if ($sessionSlug !== null) {
            $active = $roles->firstWhere('slug', $sessionSlug);
            if ($active !== null) {
                return $active;
            }
        }

        foreach (self::PRIORITY as $prioritySlug) {
            $match = $roles->firstWhere('slug', $prioritySlug);
            if ($match !== null) {
                return $match;
            }
        }

        return $roles->first();
    }

    /**
     * @return bool true nếu chuyển thành công (user thực sự giữ role này).
     */
    public static function set(User $user, string $slug): bool
    {
        if (! $user->hasRole($slug)) {
            return false;
        }

        session([self::SESSION_KEY => $slug]);

        return true;
    }
}
