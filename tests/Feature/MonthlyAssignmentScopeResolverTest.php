<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Schedule\Application\AssignMonthlySchedule\MonthlyAssignmentScopeResolver;
use Modules\Schedule\Models\MonthlySchedule;
use Tests\Support\TestingDatabaseGuard;
use Tests\TestCase;

class MonthlyAssignmentScopeResolverTest extends TestCase
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

    public function test_department_staff_scope_prefers_logged_in_department_over_first_slot_department(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $staff = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
            'department_id' => $this->seedDepartment('D2'),
        ]);

        $otherDepartmentId = $this->seedDepartment('D1');
        $subjectOneId = $this->seedSubject($otherDepartmentId, 'SUB-1');
        $subjectTwoId = $this->seedSubject((int) $staff->department_id, 'SUB-2');

        $monthlyScheduleId = $this->seedMonthlySchedule([
            'month' => 6,
            'year' => 2026,
            'slots' => [
                ['subject_id' => $subjectOneId, 'slot_type' => 'subject', 'date' => '2026-06-15 07:00:00', 'period_number' => 1],
                ['subject_id' => $subjectTwoId, 'slot_type' => 'subject', 'date' => '2026-06-16 07:00:00', 'period_number' => 1],
            ],
        ]);

        $scope = $this->app->make(MonthlyAssignmentScopeResolver::class)
            ->resolve(MonthlySchedule::query()->findOrFail($monthlyScheduleId), $staff);

        $this->assertNotNull($scope);
        $this->assertSame((int) $staff->department_id, $scope['department_id']);
        $this->assertSame([$subjectTwoId], $scope['department_subject_ids']);
    }

    private function seedDepartment(string $suffix): int
    {
        $timestamp = now();

        return DB::table('departments')->insertGetId([
            'code' => 'DEP-' . $suffix,
            'name' => 'Department ' . $suffix,
            'description' => null,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function seedSubject(int $departmentId, string $code): int
    {
        $timestamp = now();

        return DB::table('subjects')->insertGetId([
            'department_id' => $departmentId,
            'code' => $code,
            'name' => $code,
            'total_periods' => 30,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * @param array{
     *     month:int,
     *     year:int,
     *     slots:array<int, array{subject_id:int, slot_type:string, date:string, period_number:int}>
     * } $data
     */
    private function seedMonthlySchedule(array $data): int
    {
        $timestamp = now();

        $planId = DB::table('plans')->insertGetId([
            'training_batch_id' => null,
            'name' => 'Plan Test',
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

        foreach ($data['slots'] as $slot) {
            DB::table('schedule_slots')->insert([
                'monthly_schedule_id' => $monthlyScheduleId,
                'schedule_slot_group_id' => null,
                'class_id' => null,
                'teacher_id' => null,
                'subject_id' => $slot['subject_id'],
                'subject_lesson_id' => null,
                'room_id' => null,
                'slot_type' => $slot['slot_type'],
                'semester_event_id' => null,
                'event_type' => null,
                'date' => $slot['date'],
                'day_of_week' => 1,
                'period' => '1',
                'period_number' => $slot['period_number'],
                'subject' => 'SUB',
                'content' => null,
                'slot_status' => 'planned',
                'actual_content' => null,
                'note' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        return $monthlyScheduleId;
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
