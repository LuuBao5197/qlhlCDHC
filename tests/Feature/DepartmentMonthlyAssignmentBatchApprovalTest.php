<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;
use Tests\TestCase;

class DepartmentMonthlyAssignmentBatchApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function seedMonthlySchedule(Department $department, int $month, int $year, string $day, int $periodNumber): int
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

        DB::table('schedule_slots')->insert([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => null,
            'class_id' => $class->id,
            'teacher_id' => null,
            'assignment_type' => 'self_study',
            'subject_id' => $subject->id,
            'subject_lesson_id' => null,
            'room_id' => null,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => sprintf('%04d-%02d-%s 07:00:00', $year, $month, $day),
            'day_of_week' => 1,
            'period' => '1',
            'period_number' => $periodNumber,
            'subject' => $subject->code,
            'content' => null,
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return $monthlyScheduleId;
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

    private function makeTrainingOfficeUser(Position $position): User
    {
        return User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => $position,
        ]);
    }

    private function fetchBatch(int $departmentId, int $month, int $year): DepartmentMonthlyAssignmentBatch
    {
        return DepartmentMonthlyAssignmentBatch::query()
            ->where('department_id', $departmentId)
            ->where('month', $month)
            ->where('year', $year)
            ->firstOrFail();
    }

    public function test_full_two_stage_approval_flow_reaches_approved_and_completed(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);
        $head = $this->makeDepartmentUser($department, Position::DEPARTMENT_HEAD);
        $trainingHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);

        $monthlyScheduleId = $this->seedMonthlySchedule($department, 8, 2026, '10', 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch = $this->fetchBatch($department->id, 8, 2026);
        $this->assertSame('submitted', $batch->status);
        $this->assertSame('department_review', $batch->current_step);

        // PDT cannot approve before department leadership has reviewed it.
        $this->actingAs($trainingHead)
            ->postJson(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertStatus(422);

        $this->actingAs($head)
            ->postJson(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertSuccessful();

        $batch->refresh();
        $this->assertSame('submitted', $batch->status);
        $this->assertSame('training_office_review', $batch->current_step);

        $this->actingAs($trainingHead)
            ->postJson(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertSuccessful();

        $batch->refresh();
        $this->assertSame('approved', $batch->status);
        $this->assertSame('completed', $batch->current_step);
    }

    public function test_department_leadership_rejection_returns_to_draft_and_requires_department_review_again(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);
        $head = $this->makeDepartmentUser($department, Position::DEPARTMENT_HEAD);

        $monthlyScheduleId = $this->seedMonthlySchedule($department, 9, 2026, '10', 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch = $this->fetchBatch($department->id, 9, 2026);

        $this->actingAs($head)
            ->postJson(route('department-monthly-assignment-batches.return', $batch->id), ['review_note' => 'Thieu du lieu'])
            ->assertSuccessful();

        $batch->refresh();
        $this->assertSame('returned', $batch->status);
        $this->assertSame('draft', $batch->current_step);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch->refresh();
        $this->assertSame('department_review', $batch->current_step);
    }

    public function test_training_office_rejection_returns_to_draft_and_requires_department_review_again(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);
        $head = $this->makeDepartmentUser($department, Position::DEPARTMENT_HEAD);
        $trainingHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);

        $monthlyScheduleId = $this->seedMonthlySchedule($department, 10, 2026, '10', 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch = $this->fetchBatch($department->id, 10, 2026);

        $this->actingAs($head)
            ->postJson(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertSuccessful();

        $this->actingAs($trainingHead)
            ->postJson(route('department-monthly-assignment-batches.training-office-review', $batch->id), [
                'action' => 'reject',
                'reason' => 'Phan cong khong hop ly',
            ])
            ->assertSuccessful();

        $batch->refresh();
        $this->assertSame('returned', $batch->status);
        $this->assertSame('draft', $batch->current_step);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch->refresh();
        $this->assertSame('department_review', $batch->current_step);

        // PDT still cannot approve directly: Khoa leadership must review again first.
        $this->actingAs($trainingHead)
            ->postJson(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertStatus(422);
    }

    public function test_staff_cannot_approve_own_submission(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);

        $monthlyScheduleId = $this->seedMonthlySchedule($department, 11, 2026, '10', 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch = $this->fetchBatch($department->id, 11, 2026);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertForbidden();
    }

    public function test_department_head_of_other_department_cannot_approve(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);
        $otherHead = $this->makeDepartmentUser($otherDepartment, Position::DEPARTMENT_HEAD);

        $monthlyScheduleId = $this->seedMonthlySchedule($department, 12, 2026, '10', 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch = $this->fetchBatch($department->id, 12, 2026);

        $this->actingAs($otherHead)
            ->postJson(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertForbidden();
    }

    public function test_training_office_staff_position_cannot_approve_as_training_office(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);
        $head = $this->makeDepartmentUser($department, Position::DEPARTMENT_HEAD);
        $trainingStaff = $this->makeTrainingOfficeUser(Position::TRAINING_STAFF);

        $monthlyScheduleId = $this->seedMonthlySchedule($department, 1, 2027, '10', 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch = $this->fetchBatch($department->id, 1, 2027);

        $this->actingAs($head)
            ->postJson(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertSuccessful();

        $this->actingAs($trainingStaff)
            ->postJson(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertForbidden();
    }
}
