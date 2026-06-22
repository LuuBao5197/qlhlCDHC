<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\Department;

class DepartmentSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $departments = [
            [
                'code' => 'KHOA-DUOC',
                'name' => 'Khoa Dược',
                'description' => 'Đào tạo và quản lý các học phần chuyên sâu về dược lý, bào chế và thực hành cấp phát thuốc.',
                'status' => 'active',
            ],
            [
                'code' => 'KHOA-DIEU-DUONG',
                'name' => 'Khoa Điều dưỡng',
                'description' => 'Phụ trách chương trình điều dưỡng cơ bản, chăm sóc người bệnh và thực hành lâm sàng.',
                'status' => 'active',
            ],
            [
                'code' => 'KHOA-Y-SI-DA-KHOA',
                'name' => 'Khoa Y sĩ đa khoa',
                'description' => 'Đào tạo y sĩ đa khoa phục vụ tuyến y tế quân y và cộng đồng.',
                'status' => 'active',
            ],
            [
                'code' => 'KHOA-Y-HOC-CO-SO',
                'name' => 'Khoa Y học cơ sở',
                'description' => 'Phụ trách các môn nền tảng về sinh lý, vi sinh, miễn dịch và hóa sinh y học.',
                'status' => 'active',
            ],
            [
                'code' => 'KHOA-KHOA-HOC-CO-BAN',
                'name' => 'Khoa Khoa học cơ bản',
                'description' => 'Giảng dạy các học phần nền tảng như y đức, giao tiếp y khoa và tin học ứng dụng.',
                'status' => 'active',
            ],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                [
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'status' => $department['status'],
                ]
            );
        }
    }
}
