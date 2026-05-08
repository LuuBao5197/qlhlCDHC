<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class TeacherDailyLogAccessTest extends TestCase
{
    public function test_training_office_cannot_submit_non_today_date_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->withoutMiddleware();

        $user = new User([
            'name' => 'Training Office',
            'email' => 'training.office@example.com',
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/duty-log/edit?date=2026-05-05')
            ->post('/duty-log/submit', [
                'date' => '2026-05-05',
                'training_plan_comment' => 'test comment',
            ]);

        $response->assertRedirect('/duty-log/edit?date=2026-05-05');
        $response->assertSessionHasErrors(['date']);
    }

    public function test_department_staff_cannot_access_edit_page(): void
    {
        $user = new User([
            'name' => 'Department Staff',
            'email' => 'department.staff@example.com',
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/duty-log/edit?date=2026-05-07');

        $response->assertForbidden();
    }
}
