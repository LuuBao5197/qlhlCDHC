<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;
use Tests\TestCase;

class MonthlyAssignmentDossierApprovalWorkflowTest extends TestCase
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

    private function makeLeadershipUser(Position $position): User
    {
        return User::factory()->create([
            'role' => User::ROLE_LEADERSHIP,
            'status' => User::STATUS_APPROVED,
            'position' => $position,
        ]);
    }

    /**
     * Dua 1 batch phan cong cua 1 Khoa di het 2 vong duyet (Lanh dao Khoa + PDT) -> status=approved.
     */
    private function readyDepartmentBatch(Department $department, int $month, int $year, string $day): DepartmentMonthlyAssignmentBatch
    {
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);
        $head = $this->makeDepartmentUser($department, Position::DEPARTMENT_HEAD);
        $trainingHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);

        $monthlyScheduleId = $this->seedMonthlySchedule($department, $month, $year, $day, 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $monthlyScheduleId))
            ->assertSuccessful();

        $batch = DepartmentMonthlyAssignmentBatch::query()
            ->where('department_id', $department->id)
            ->where('month', $month)
            ->where('year', $year)
            ->firstOrFail();

        $this->actingAs($head)
            ->postJson(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertSuccessful();

        $this->actingAs($trainingHead)
            ->postJson(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertSuccessful();

        return $batch->refresh();
    }

    public function test_full_five_step_workflow_from_department_to_bgh(): void
    {
        $month = 3;
        $year = 2027;

        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $this->readyDepartmentBatch($departmentA, $month, $year, '05');
        $this->readyDepartmentBatch($departmentB, $month, $year, '06');

        $pdtStaff = $this->makeTrainingOfficeUser(Position::TRAINING_STAFF);
        $pdtHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);
        $principal = $this->makeLeadershipUser(Position::PRINCIPAL);

        $this->actingAs($pdtStaff)
            ->post(route('monthly-assignment-dossiers.store'), ['month' => $month, 'year' => $year])
            ->assertRedirect();

        $dossier = MonthlyAssignmentDossier::query()
            ->where('month', $month)->where('year', $year)->firstOrFail();
        $this->assertSame('draft', $dossier->status);
        $this->assertSame(2, $dossier->batches()->count());

        $this->actingAs($pdtStaff)
            ->postJson(route('monthly-assignment-dossiers.submit', $dossier->id))
            ->assertSuccessful();

        $dossier->refresh();
        $this->assertSame('submitted', $dossier->status);
        $this->assertSame('training_office_review', $dossier->current_step);

        // BGH cannot approve before Lanh dao PDT.
        $this->actingAs($principal)
            ->postJson(route('monthly-assignment-dossiers.leadership-review', $dossier->id), ['action' => 'approve'])
            ->assertStatus(422);

        $this->actingAs($pdtHead)
            ->postJson(route('monthly-assignment-dossiers.training-office-review', $dossier->id), ['action' => 'approve'])
            ->assertSuccessful();

        $dossier->refresh();
        $this->assertSame('submitted', $dossier->status);
        $this->assertSame('leadership_review', $dossier->current_step);

        $this->actingAs($principal)
            ->postJson(route('monthly-assignment-dossiers.leadership-review', $dossier->id), ['action' => 'approve'])
            ->assertSuccessful();

        $dossier->refresh();
        $this->assertSame('approved', $dossier->status);
        $this->assertSame('completed', $dossier->current_step);
    }

    public function test_build_dossier_is_blocked_and_lists_missing_departments(): void
    {
        $month = 4;
        $year = 2027;

        $departmentA = Department::factory()->create(['name' => 'Khoa CNTT']);
        $departmentB = Department::factory()->create(['name' => 'Khoa Dien']);

        $this->readyDepartmentBatch($departmentA, $month, $year, '05');

        // Department B has scheduled subjects for the month but never submitted a batch.
        $this->seedMonthlySchedule($departmentB, $month, $year, '06', 1);

        $pdtStaff = $this->makeTrainingOfficeUser(Position::TRAINING_STAFF);

        $response = $this->actingAs($pdtStaff)
            ->post(route('monthly-assignment-dossiers.store'), ['month' => $month, 'year' => $year]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('dossier');

        $message = session('errors')->first('dossier');
        $this->assertStringContainsString('Khoa Dien', $message);

        $this->assertDatabaseMissing('monthly_assignment_dossiers', [
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function test_training_office_rejection_returns_dossier_to_draft(): void
    {
        $month = 5;
        $year = 2027;

        $department = Department::factory()->create();
        $this->readyDepartmentBatch($department, $month, $year, '05');

        $pdtStaff = $this->makeTrainingOfficeUser(Position::TRAINING_STAFF);
        $pdtHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);

        $this->actingAs($pdtStaff)
            ->post(route('monthly-assignment-dossiers.store'), ['month' => $month, 'year' => $year])
            ->assertRedirect();

        $dossier = MonthlyAssignmentDossier::query()->where('month', $month)->where('year', $year)->firstOrFail();

        $this->actingAs($pdtStaff)
            ->postJson(route('monthly-assignment-dossiers.submit', $dossier->id))
            ->assertSuccessful();

        $this->actingAs($pdtHead)
            ->postJson(route('monthly-assignment-dossiers.training-office-review', $dossier->id), [
                'action' => 'reject',
                'reason' => 'Can kiem tra lai so lieu',
            ])
            ->assertSuccessful();

        $dossier->refresh();
        $this->assertSame('returned', $dossier->status);
        $this->assertSame('draft', $dossier->current_step);
    }
}
