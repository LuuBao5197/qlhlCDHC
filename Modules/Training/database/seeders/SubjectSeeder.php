<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;

class SubjectSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $departments = Department::query()
            ->whereIn('code', [
                'KHOA-DUOC',
                'KHOA-DIEU-DUONG',
                'KHOA-Y-SI-DA-KHOA',
                'KHOA-Y-HOC-CO-SO',
                'KHOA-KHOA-HOC-CO-BAN',
            ])
            ->get()
            ->keyBy('code');

        $subjectsByDepartment = [
            'KHOA-DUOC' => [
                ['code' => 'DUOC-001', 'name' => 'Dược lý cơ bản', 'total_periods' => 45],
                ['code' => 'DUOC-002', 'name' => 'Bào chế thuốc', 'total_periods' => 60],
                ['code' => 'DUOC-003', 'name' => 'Dược lâm sàng đại cương', 'total_periods' => 45],
            ],
            'KHOA-DIEU-DUONG' => [
                ['code' => 'DD-001', 'name' => 'Điều dưỡng cơ bản', 'total_periods' => 60],
                ['code' => 'DD-002', 'name' => 'Chăm sóc người bệnh nội khoa', 'total_periods' => 45],
                ['code' => 'DD-003', 'name' => 'Kiểm soát nhiễm khuẩn', 'total_periods' => 30],
            ],
            'KHOA-Y-SI-DA-KHOA' => [
                ['code' => 'YSDK-001', 'name' => 'Giải phẫu - sinh lý', 'total_periods' => 60],
                ['code' => 'YSDK-002', 'name' => 'Nội khoa cơ sở', 'total_periods' => 45],
                ['code' => 'YSDK-003', 'name' => 'Ngoại khoa cơ sở', 'total_periods' => 45],
            ],
            'KHOA-Y-HOC-CO-SO' => [
                ['code' => 'YHCS-001', 'name' => 'Vi sinh - ký sinh trùng', 'total_periods' => 45],
                ['code' => 'YHCS-002', 'name' => 'Sinh lý bệnh - miễn dịch', 'total_periods' => 45],
                ['code' => 'YHCS-003', 'name' => 'Hóa sinh y học', 'total_periods' => 45],
            ],
            'KHOA-KHOA-HOC-CO-BAN' => [
                ['code' => 'KHCB-001', 'name' => 'Pháp luật và y đức', 'total_periods' => 30],
                ['code' => 'KHCB-002', 'name' => 'Tâm lý y học và giao tiếp', 'total_periods' => 30],
                ['code' => 'KHCB-003', 'name' => 'Tin học ứng dụng trong y tế', 'total_periods' => 30],
            ],
        ];

        foreach ($subjectsByDepartment as $departmentCode => $subjects) {
            $department = $departments->get($departmentCode);

            if (! $department) {
                continue;
            }

            foreach ($subjects as $subject) {
                Subject::firstOrCreate(
                    ['code' => $subject['code']],
                    [
                        'department_id' => $department->id,
                        'name' => $subject['name'],
                        'total_periods' => $subject['total_periods'],
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
