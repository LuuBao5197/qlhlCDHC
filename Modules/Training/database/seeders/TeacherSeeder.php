<?php

namespace Modules\Training\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Training\Models\Department;
use Modules\Training\Models\Teacher;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $defaultDepartment = Department::where('code', 'KCNTT')->first() ?? Department::query()->first();

        $seedTeachers = [
            ['teacher_code' => 'GV-0001', 'name' => 'Giang vien 01', 'status' => 'active'],
            ['teacher_code' => 'GV-0002', 'name' => 'Giang vien 02', 'status' => 'active'],
            ['teacher_code' => 'GV-0003', 'name' => 'Giang vien 03', 'status' => 'inactive'],
        ];

        foreach ($seedTeachers as $teacher) {
            Teacher::updateOrCreate(
                ['teacher_code' => $teacher['teacher_code']],
                [
                    'name' => $teacher['name'],
                    'status' => $teacher['status'],
                    'department_id' => $defaultDepartment?->id,
                ]
            );
        }

        User::query()
            ->where('role', User::ROLE_TEACHER)
            ->get()
            ->each(function (User $user) use ($defaultDepartment): void {
                $teacherCode = $user->employee_code ?: 'GV-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);

                Teacher::updateOrCreate(
                    ['teacher_code' => $teacherCode],
                    [
                        'name' => $user->name,
                        'status' => $user->isApproved() ? 'active' : 'inactive',
                        'department_id' => $user->department_id ?? $defaultDepartment?->id,
                    ]
                );
            });
    }
}