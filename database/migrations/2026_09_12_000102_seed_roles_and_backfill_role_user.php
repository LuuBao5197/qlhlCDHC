<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Slug mới thay cho tổ hợp (role, position) cũ — xem Report/Progress kế hoạch
     * "role_user many-to-many". Mỗi user có thể giữ nhiều role cùng lúc.
     */
    private const ROLES = [
        ['slug' => 'admin', 'name' => 'Quản trị hệ thống'],
        ['slug' => 'student', 'name' => 'Sinh viên'],
        ['slug' => 'teacher', 'name' => 'Giảng viên'],
        ['slug' => 'department_staff', 'name' => 'Giáo vụ khoa'],
        ['slug' => 'department_head', 'name' => 'Chủ nhiệm khoa'],
        ['slug' => 'training_office', 'name' => 'Chuyên viên phòng đào tạo'],
        ['slug' => 'training_office_head', 'name' => 'Lãnh đạo phòng đào tạo'],
        ['slug' => 'leadership', 'name' => 'Ban giám hiệu'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::ROLES as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $roleIds = DB::table('roles')->pluck('id', 'slug');

        $users = DB::table('users')->select('id', 'role', 'position')->get();

        $pivotRows = [];
        foreach ($users as $user) {
            foreach ($this->resolveSlugsFor($user->role, $user->position) as $slug) {
                if (! isset($roleIds[$slug])) {
                    continue;
                }

                $pivotRows[] = [
                    'user_id' => $user->id,
                    'role_id' => $roleIds[$slug],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($pivotRows, 500) as $chunk) {
            DB::table('role_user')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        DB::table('role_user')->truncate();
        DB::table('roles')->whereIn('slug', array_column(self::ROLES, 'slug'))->delete();
    }

    /**
     * @return list<string>
     */
    private function resolveSlugsFor(?string $role, ?string $position): array
    {
        return match ($role) {
            'admin' => ['admin'],
            'student' => ['student'],
            'teacher' => ['teacher'],
            'department_staff' => $position === 'department_head'
                ? ['department_staff', 'department_head']
                : ['department_staff'],
            'training_office' => in_array($position, ['training_head', 'training_deputy_head'], true)
                ? ['training_office', 'training_office_head']
                : ['training_office'],
            'leadership' => ['leadership'],
            default => [],
        };
    }
};
