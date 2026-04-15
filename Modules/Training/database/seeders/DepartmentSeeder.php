<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'PDT', 'name' => 'Phong Dao Tao', 'description' => 'Quan ly va dieu phoi dao tao', 'status' => 'active'],
            ['code' => 'KCNTT', 'name' => 'Khoa Cong Nghe Thong Tin', 'description' => 'Quan ly lop va mon hoc CNTT', 'status' => 'active'],
            ['code' => 'KQS', 'name' => 'Khoa Quan Su', 'description' => 'Don vi huan luyen chuyen mon', 'status' => 'active'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                $department
            );
        }

        $mapping = [
            'training@example.com' => 'PDT',
            'department@example.com' => 'KCNTT',
            'teacher@example.com' => 'KCNTT',
            'student@example.com' => 'KCNTT',
        ];

        foreach ($mapping as $email => $code) {
            $user = User::where('email', $email)->first();
            $department = Department::where('code', $code)->first();

            if ($user && $department) {
                $user->update([
                    'department_id' => $department->id,
                    'employee_code' => $user->employee_code ?? strtoupper(substr($code, 0, 3)) . '-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
