<?php

namespace Modules\Training\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\Department;

class DemoUserSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $departmentDuoc = Department::where('code', 'KHOA-DUOC')->first();
        $departmentDieuDuong = Department::where('code', 'KHOA-DIEU-DUONG')->first();
        $departmentYsdk = Department::where('code', 'KHOA-Y-SI-DA-KHOA')->first();

        $users = [
            [
                'email' => 'admin@qhcdhc.local',
                'name' => 'Quản trị hệ thống',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'ADMIN-0001',
                'department_id' => null,
                'phone' => '0901000001',
            ],
            [
                'email' => 'lanh-dao-1@demo.local',
                'name' => 'Đại tá Nguyễn Văn Minh',
                'role' => User::ROLE_LEADERSHIP,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'LD-0001',
                'department_id' => null,
                'phone' => '0901000002',
            ],
            [
                'email' => 'lanh-dao-2@demo.local',
                'name' => 'Thượng tá Trần Thị Hồng Nhung',
                'role' => User::ROLE_LEADERSHIP,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'LD-0002',
                'department_id' => null,
                'phone' => '0901000003',
            ],
            [
                'email' => 'pdt-1@demo.local',
                'name' => 'Cử nhân Phạm Anh Tuấn',
                'role' => User::ROLE_TRAINING_OFFICE,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'PDT-0001',
                'department_id' => null,
                'phone' => '0901000004',
            ],
            [
                'email' => 'pdt-2@demo.local',
                'name' => 'Cử nhân Nguyễn Thu Hà',
                'role' => User::ROLE_TRAINING_OFFICE,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'PDT-0002',
                'department_id' => null,
                'phone' => '0901000005',
            ],
            [
                'email' => 'khoa-duoc-1@demo.local',
                'name' => 'DS. Lê Hoàng Nam',
                'role' => User::ROLE_DEPARTMENT_STAFF,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'KD-0001',
                'department_id' => $departmentDuoc?->id,
                'phone' => '0901000006',
            ],
            [
                'email' => 'khoa-dieu-duong-1@demo.local',
                'name' => 'ĐD. Trần Thị Mai Anh',
                'role' => User::ROLE_DEPARTMENT_STAFF,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'KDD-0001',
                'department_id' => $departmentDieuDuong?->id,
                'phone' => '0901000007',
            ],
            [
                'email' => 'giang-vien-1@demo.local',
                'name' => 'Giảng viên Vũ Đức Long',
                'role' => User::ROLE_TEACHER,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'GV-0001',
                'department_id' => $departmentDuoc?->id,
                'phone' => '0901000008',
            ],
            [
                'email' => 'giang-vien-2@demo.local',
                'name' => 'Giảng viên Hoàng Thị Thu Trang',
                'role' => User::ROLE_TEACHER,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'GV-0002',
                'department_id' => $departmentYsdk?->id,
                'phone' => '0901000009',
            ],
            [
                'email' => 'hoc-vien-1@demo.local',
                'name' => 'Học viên Nguyễn Gia Hân',
                'role' => User::ROLE_STUDENT,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'HV-0001',
                'department_id' => null,
                'phone' => '0901000010',
            ],
            [
                'email' => 'hoc-vien-2@demo.local',
                'name' => 'Học viên Lê Minh Khang',
                'role' => User::ROLE_STUDENT,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'HV-0002',
                'department_id' => null,
                'phone' => '0901000011',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password123',
                    'role' => $userData['role'],
                    'status' => $userData['status'],
                    'department_id' => $userData['department_id'],
                    'employee_code' => $userData['employee_code'],
                    'phone' => $userData['phone'],
                ]
            );

            // Email demo là địa chỉ giả, không thể nhận mail mời kích hoạt — đánh dấu đã
            // kích hoạt sẵn để màn Admin không hiện nút "Gửi lại email" (forceFill vì
            // email_verified_at không nằm trong $fillable).
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }
    }
}
