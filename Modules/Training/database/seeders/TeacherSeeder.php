<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\Department;
use Modules\Training\Models\Teacher;

class TeacherSeeder extends Seeder
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
        $departmentYhocCoSo = Department::where('code', 'KHOA-Y-HOC-CO-SO')->first();
        $departmentKhoaHocCoBan = Department::where('code', 'KHOA-KHOA-HOC-CO-BAN')->first();

        $teachers = [
            ['teacher_code' => 'GV-DUOC-01', 'name' => 'ThS. Dược sĩ Nguyễn Minh Đức', 'department_id' => $departmentDuoc?->id],
            ['teacher_code' => 'GV-DUOC-02', 'name' => 'ThS. Dược sĩ Trần Thu Hà', 'department_id' => $departmentDuoc?->id],
            ['teacher_code' => 'GV-DUONG-01', 'name' => 'CN. Điều dưỡng Lê Thị Hồng', 'department_id' => $departmentDieuDuong?->id],
            ['teacher_code' => 'GV-DUONG-02', 'name' => 'CN. Điều dưỡng Phạm Đức Anh', 'department_id' => $departmentDieuDuong?->id],
            ['teacher_code' => 'GV-YSDK-01', 'name' => 'BS. Nguyễn Hoàng Nam', 'department_id' => $departmentYsdk?->id],
            ['teacher_code' => 'GV-YHCS-01', 'name' => 'TS. Trần Thị Lan Phương', 'department_id' => $departmentYhocCoSo?->id],
            ['teacher_code' => 'GV-KHCB-01', 'name' => 'ThS. Lê Quốc Bảo', 'department_id' => $departmentKhoaHocCoBan?->id],
        ];

        foreach ($teachers as $teacher) {
            Teacher::firstOrCreate(
                ['teacher_code' => $teacher['teacher_code']],
                [
                    'name' => $teacher['name'],
                    'status' => 'active',
                    'department_id' => $teacher['department_id'],
                ]
            );
        }
    }
}
