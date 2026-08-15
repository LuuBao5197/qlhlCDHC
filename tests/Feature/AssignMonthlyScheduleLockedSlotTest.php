<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Training\Models\DailyTrainingLog;
use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;
use Tests\TestCase;

class AssignMonthlyScheduleLockedSlotTest extends TestCase
{
    use RefreshDatabase;

    private function seedMonthlyScheduleWithSlot(Department $department, int $month, int $year, string $day, string $slotStatus = 'planned'): array
    {
        $timestamp = now();

        $subject = Subject::factory()->create(['department_id' => $department->id]);
        $class = TrainingClass::factory()->create();

        $planId = DB::table('plans')->insertGetId([
            'training_batch_id' => null,
            'name' => 'Plan ' . $department->code . '-' . $day,
            'semester' => 1,
            'year' => $year,
            'file_path' => null,
            'description' => null,
            'created_by' => null,
            'submitted_by' => null,
            'submitted_at' => null,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => sprintf('%04d-%02d-01', $year, $month),
            'effective_to' => sprintf('%04d-%02d-28', $year, $month),
            'approved_version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => $month,
            'year' => $year,
            'created_by' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $slotId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => null,
            'class_id' => $class->id,
            'teacher_id' => null,
            'assignment_type' => null,
            'subject_id' => $subject->id,
            'subject_lesson_id' => null,
            'room_id' => null,
            'slot_type' => 'subject',
            'assignment_source' => 'internal',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => sprintf('%04d-%02d-%s 07:00:00', $year, $month, $day),
            'day_of_week' => 1,
            'period' => '1',
            'period_number' => 1,
            'subject' => $subject->code,
            'content' => 'Noi dung ban dau',
            'slot_status' => $slotStatus,
            'actual_content' => null,
            'note' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return [
            'monthly_schedule_id' => $monthlyScheduleId,
            'slot_id' => $slotId,
        ];
    }

    private function makeDepartmentUser(Department $department, Position $position): User
    {
        return User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
            'position' => $position,
            'department_id' => $department->id,
        ]);
    }

    public function test_cannot_edit_a_slot_that_was_cancelled_for_a_holiday(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);

        $seed = $this->seedMonthlyScheduleWithSlot($department, 9, 2026, '02', 'cancelled');

        $this->actingAs($staff)
            ->postJson(route('monthly-schedule.assignment.save', $seed['monthly_schedule_id']), [
                'changes' => [
                    ['slot_id' => $seed['slot_id'], 'content' => 'Noi dung moi'],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame('Noi dung ban dau', DB::table('schedule_slots')->where('id', $seed['slot_id'])->value('content'));
    }

    public function test_cannot_edit_a_slot_that_already_has_a_daily_training_log(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);

        $seed = $this->seedMonthlyScheduleWithSlot($department, 9, 2026, '03', 'planned');

        DailyTrainingLog::factory()->create([
            'schedule_slot_id' => $seed['slot_id'],
        ]);

        $this->actingAs($staff)
            ->postJson(route('monthly-schedule.assignment.save', $seed['monthly_schedule_id']), [
                'changes' => [
                    ['slot_id' => $seed['slot_id'], 'content' => 'Noi dung moi'],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame('Noi dung ban dau', DB::table('schedule_slots')->where('id', $seed['slot_id'])->value('content'));
    }

    public function test_can_still_edit_a_normal_untaught_slot(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);

        $seed = $this->seedMonthlyScheduleWithSlot($department, 9, 2026, '04', 'planned');

        $this->actingAs($staff)
            ->postJson(route('monthly-schedule.assignment.save', $seed['monthly_schedule_id']), [
                'changes' => [
                    ['slot_id' => $seed['slot_id'], 'content' => 'Noi dung moi'],
                ],
            ])
            ->assertSuccessful();

        $this->assertSame('Noi dung moi', DB::table('schedule_slots')->where('id', $seed['slot_id'])->value('content'));
    }
}
