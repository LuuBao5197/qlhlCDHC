<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Training\Models\Room;
use Tests\TestCase;

class ManageTrainingClassesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_a_training_class_with_class_details(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);
        $room = Room::query()->create([
            'code' => 'P101',
            'name' => 'Phong 101',
            'capacity' => 40,
            'room_type' => 'classroom',
            'status' => 'active',
        ]);

        $createResponse = $this->actingAs($admin)->postJson('/management/training-classes', [
            'code' => 'CLS-001',
            'name' => 'Lop 01',
            'course_year' => 2026,
            'total_students' => 35,
            'default_room_id' => $room->id,
            'status' => 'active',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('total_students', 35)
            ->assertJsonPath('default_room.id', $room->id);

        $classId = $createResponse->json('id');

        $this->actingAs($admin)->putJson("/management/training-classes/{$classId}", [
            'code' => 'CLS-001',
            'name' => 'Lop 01 cap nhat',
            'course_year' => 2026,
            'total_students' => 40,
            'default_room_id' => null,
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('total_students', 40)
            ->assertJsonPath('default_room_id', null)
            ->assertJsonPath('default_room', null);

        $this->assertDatabaseHas('classes', [
            'id' => $classId,
            'total_students' => 40,
            'default_room_id' => null,
        ]);
    }

    public function test_total_students_is_required_when_saving_a_training_class(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $this->actingAs($admin)->postJson('/management/training-classes', [
            'code' => 'CLS-002',
            'name' => 'Lop 02',
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('total_students');
    }
}
