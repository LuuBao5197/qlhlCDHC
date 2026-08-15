<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Training\Models\DailyTrainingLog;
use Modules\Training\Models\Department;
use Modules\Training\Models\HolidayCalendar;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;
use Tests\TestCase;

class CancelSlotsForHolidayTest extends TestCase
{
    use RefreshDatabase;

    private function seedMonthlyScheduleWithSlot(Department $department, int $month, int $year, string $day, int $periodNumber): array
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

    private function makeTrainingOfficeUser(Position $position): User
    {
        return User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => $position,
        ]);
    }

    public function test_cancel_slots_for_holiday_marks_untaught_slots_and_reopens_approved_batch(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);
        $head = $this->makeDepartmentUser($department, Position::DEPARTMENT_HEAD);
        $trainingHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);

        $seed = $this->seedMonthlyScheduleWithSlot($department, 9, 2026, '02', 1);

        $this->actingAs($staff)
            ->postJson(route('department-monthly-assignment-batches.submit', $seed['monthly_schedule_id']))
            ->assertSuccessful();

        $batch = DepartmentMonthlyAssignmentBatch::query()
            ->where('department_id', $department->id)
            ->where('month', 9)
            ->where('year', 2026)
            ->firstOrFail();

        $this->actingAs($head)
            ->postJson(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertSuccessful();

        $this->actingAs($trainingHead)
            ->postJson(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertSuccessful();

        $batch->refresh();
        $this->assertSame('approved', $batch->status);

        $holiday = HolidayCalendar::query()->create([
            'name' => 'Nghi le dot xuat',
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-02',
            'is_active' => true,
        ]);

        $this->actingAs($trainingHead)
            ->post(route('holiday-calendar.cancel-slots', $holiday->id))
            ->assertRedirect();

        $this->assertSame('cancelled', DB::table('schedule_slots')->where('id', $seed['slot_id'])->value('slot_status'));
        $this->assertSame($holiday->id, DB::table('schedule_slots')->where('id', $seed['slot_id'])->value('holiday_calendar_id'));

        $batch->refresh();
        $this->assertSame('returned', $batch->status);
        $this->assertSame('draft', $batch->current_step);
        $this->assertNull($batch->training_office_reviewed_by);
    }

    public function test_cancel_slots_for_holiday_skips_slots_that_are_already_taught(): void
    {
        $department = Department::factory()->create();
        $trainingHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);

        $seed = $this->seedMonthlyScheduleWithSlot($department, 10, 2026, '05', 1);

        DailyTrainingLog::factory()->create([
            'schedule_slot_id' => $seed['slot_id'],
        ]);

        $holiday = HolidayCalendar::query()->create([
            'name' => 'Nghi le dot xuat 2',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'is_active' => true,
        ]);

        $this->actingAs($trainingHead)
            ->post(route('holiday-calendar.cancel-slots', $holiday->id))
            ->assertRedirect();

        $this->assertSame('planned', DB::table('schedule_slots')->where('id', $seed['slot_id'])->value('slot_status'));
    }

    public function test_department_staff_cannot_cancel_slots_for_holiday(): void
    {
        $department = Department::factory()->create();
        $staff = $this->makeDepartmentUser($department, Position::DEPARTMENT_STAFF);

        $holiday = HolidayCalendar::query()->create([
            'name' => 'Nghi le dot xuat 3',
            'start_date' => '2026-11-11',
            'end_date' => '2026-11-11',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('holiday-calendar.cancel-slots', $holiday->id))
            ->assertForbidden();
    }

    public function test_cancel_slots_for_holiday_covers_the_whole_declared_date_range(): void
    {
        $department = Department::factory()->create();
        $trainingHead = $this->makeTrainingOfficeUser(Position::TRAINING_HEAD);

        $firstDay = $this->seedMonthlyScheduleWithSlot($department, 12, 2026, '01', 1);
        $middleDay = $this->seedMonthlyScheduleWithSlot($department, 12, 2026, '02', 1);
        $lastDay = $this->seedMonthlyScheduleWithSlot($department, 12, 2026, '03', 1);
        $afterRange = $this->seedMonthlyScheduleWithSlot($department, 12, 2026, '04', 1);

        $holiday = HolidayCalendar::query()->create([
            'name' => 'Nghi Tet',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
            'is_active' => true,
        ]);

        $this->actingAs($trainingHead)
            ->post(route('holiday-calendar.cancel-slots', $holiday->id))
            ->assertRedirect();

        $this->assertSame('cancelled', DB::table('schedule_slots')->where('id', $firstDay['slot_id'])->value('slot_status'));
        $this->assertSame('cancelled', DB::table('schedule_slots')->where('id', $middleDay['slot_id'])->value('slot_status'));
        $this->assertSame('cancelled', DB::table('schedule_slots')->where('id', $lastDay['slot_id'])->value('slot_status'));
        $this->assertSame('planned', DB::table('schedule_slots')->where('id', $afterRange['slot_id'])->value('slot_status'));
    }
}
