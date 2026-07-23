<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@qhcdhc.local');
        $password = (string) env('ADMIN_PASSWORD', 'password123');
        $name = (string) env('ADMIN_NAME', 'Quản trị hệ thống');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_APPROVED,
                'employee_code' => 'ADMIN-0001',
                'department_id' => null,
                'phone' => null,
            ]
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
