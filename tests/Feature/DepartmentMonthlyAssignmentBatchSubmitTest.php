<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch\SubmitDepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlySchedule;
use Tests\Support\TestingDatabaseGuard;
use Tests\TestCase;

class DepartmentMonthlyAssignmentBatchSubmitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetRelevantTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_submit_blocks_when_any_subject_slot_is_missing_teacher(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $actor = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        [$monthlyScheduleId] = $this->seedMonthlyAssignmentContext(
            departmentSuffix: 'A',
            month: 6,
            year: 2026,
            slotTeacherId: null
        );

        $service = $this->app->make(SubmitDepartmentMonthlyAssignmentBatch::class);

        try {
            $service->handle(MonthlySchedule::query()->findOrFail($monthlyScheduleId), $actor);
            $this->fail('Expected submit to fail when a slot is missing a teacher.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('batch', $exception->errors());
            $this->assertStringContainsString('chua duoc phan cong giang vien', implode(' ', $exception->errors()['batch']));
        }
    }

    public function test_submit_blocks_when_teacher_conflict_exists_outside_batch(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $actor = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $teacherId = $this->seedTeacher('T', 'Teacher T');

        [$monthlyScheduleId] = $this->seedMonthlyAssignmentContext(
            departmentSuffix: 'B',
            month: 6,
            year: 2026,
            slotTeacherId: $teacherId
        );

        [$conflictMonthlyScheduleId] = $this->seedMonthlyScheduleWithSlot([
            'departmentSuffix' => 'C',
            'month' => 6,
            'year' => 2026,
            'date' => '2026-06-15 07:00:00',
            'periodNumber' => 1,
            'teacherId' => $teacherId,
        ]);

        $this->assertNotSame($monthlyScheduleId, $conflictMonthlyScheduleId);

        $service = $this->app->make(SubmitDepartmentMonthlyAssignmentBatch::class);

        try {
            $service->handle(MonthlySchedule::query()->findOrFail($monthlyScheduleId), $actor);
            $this->fail('Expected submit to fail when a teacher conflict exists.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('teacher_conflict', $exception->errors());
        }
    }

    public function test_submit_succeeds_when_all_subject_slots_are_assigned_and_conflict_free(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $actor = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $teacherId = $this->seedTeacher('S', 'Teacher S');
        $roomId = $this->seedRoom('R', 'Room R');

        [$monthlyScheduleId] = $this->seedMonthlyScheduleWithSlot([
            'departmentSuffix' => 'D',
            'month' => 6,
            'year' => 2026,
            'date' => '2026-06-15 07:00:00',
            'periodNumber' => 1,
            'teacherId' => $teacherId,
            'roomId' => $roomId,
        ]);

        $service = $this->app->make(SubmitDepartmentMonthlyAssignmentBatch::class);
        $batch = $service->handle(MonthlySchedule::query()->findOrFail($monthlyScheduleId), $actor);

        $this->assertSame('submitted', $batch->status);
        $this->assertSame($actor->id, (int) $batch->submitted_by);
        $this->assertDatabaseHas('department_monthly_assignment_batches', [
            'id' => $batch->id,
            'status' => 'submitted',
            'submitted_by' => $actor->id,
        ]);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function seedMonthlyAssignmentContext(
        string $departmentSuffix,
        int $month,
        int $year,
        ?int $slotTeacherId
    ): array {
        return $this->seedMonthlyScheduleWithSlot([
            'departmentSuffix' => $departmentSuffix,
            'month' => $month,
            'year' => $year,
            'date' => sprintf('%04d-%02d-15 07:00:00', $year, $month),
            'periodNumber' => 1,
            'teacherId' => $slotTeacherId,
        ]);
    }

    /**
     * @param array{
     *     departmentSuffix:string,
     *     month:int,
     *     year:int,
     *     date:string,
     *     periodNumber:int,
     *     teacherId:?int,
     *     roomId?:?int
     * } $data
     *
     * @return array{0:int,1:int}
     */
    private function seedMonthlyScheduleWithSlot(array $data): array
    {
        $timestamp = now();

        $departmentId = DB::table('departments')->insertGetId([
            'code' => 'DEP-' . $data['departmentSuffix'],
            'name' => 'Department ' . $data['departmentSuffix'],
            'description' => null,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $classId = DB::table('classes')->insertGetId([
            'code' => 'CLS-' . $data['departmentSuffix'],
            'name' => 'Class ' . $data['departmentSuffix'],
            'course_year' => 2026,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $subjectId = DB::table('subjects')->insertGetId([
            'department_id' => $departmentId,
            'code' => 'SUB-' . $data['departmentSuffix'],
            'name' => 'Subject ' . $data['departmentSuffix'],
            'total_periods' => 30,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'training_batch_id' => null,
            'name' => 'Plan ' . $data['departmentSuffix'],
            'semester' => 1,
            'year' => $data['year'],
            'file_path' => null,
            'description' => null,
            'created_by' => null,
            'submitted_by' => null,
            'submitted_at' => null,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => sprintf('%04d-%02d-01', $data['year'], $data['month']),
            'effective_to' => sprintf('%04d-%02d-28', $data['year'], $data['month']),
            'approved_version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => $data['month'],
            'year' => $data['year'],
            'created_by' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $roomId = $data['roomId'] ?? null;
        if ($roomId === null) {
            $roomId = $this->seedRoom($data['departmentSuffix'], 'Room ' . $data['departmentSuffix']);
        }

        $slotId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => null,
            'class_id' => $classId,
            'teacher_id' => $data['teacherId'],
            'subject_id' => $subjectId,
            'subject_lesson_id' => null,
            'room_id' => $roomId,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => $data['date'],
            'day_of_week' => 1,
            'period' => '1',
            'period_number' => $data['periodNumber'],
            'subject' => 'SUB-' . $data['departmentSuffix'],
            'content' => null,
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return [$monthlyScheduleId, $slotId];
    }

    private function seedTeacher(string $suffix, string $name): int
    {
        $timestamp = now();
        $departmentId = DB::table('departments')->insertGetId([
            'code' => 'DEP-T-' . $suffix,
            'name' => 'Teacher Dept ' . $suffix,
            'description' => null,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return DB::table('teachers')->insertGetId([
            'teacher_code' => 'GV-' . $suffix,
            'name' => $name,
            'status' => 'active',
            'department_id' => $departmentId,
            'user_id' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function seedRoom(string $suffix, string $name): int
    {
        $timestamp = now();

        return DB::table('rooms')->insertGetId([
            'code' => 'RM-' . $suffix,
            'name' => $name,
            'capacity' => 40,
            'room_type' => 'classroom',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function resetRelevantTables(): void
    {
        TestingDatabaseGuard::assertSafeBeforeBoot();
        TestingDatabaseGuard::assertSafeAfterBoot();

        $tables = [
            'department_monthly_assignment_batch_slots',
            'department_monthly_assignment_batches',
            'schedule_slots',
            'schedule_slot_groups',
            'monthly_schedules',
            'plans',
            'classes',
            'subjects',
            'subject_lessons',
            'rooms',
            'teachers',
            'departments',
            'users',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }
}
