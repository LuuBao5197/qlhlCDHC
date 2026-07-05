<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TestingDatabaseGuard;
use Tests\TestCase;

class AssignMonthlyScheduleSaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetRelevantTables();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_saves_a_large_json_change_set_exceeding_one_thousand_equivalent_form_fields(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $departmentId = $this->seedDepartment('D1');
        $teacherId = $this->seedTeacher($departmentId, 'Teacher D1', 'GV-D1');
        $roomId = $this->seedRoom('R1', 'Room 1');
        $subjectId = $this->seedSubject($departmentId, 'SUB-D1');
        $lessonId = $this->seedSubjectLesson($subjectId, 1, 'Lesson 1');
        $user = $this->seedDepartmentStaff($departmentId);

        $context = $this->seedMonthlyScheduleContext([
            'label' => 'bulk',
            'departmentId' => $departmentId,
            'month' => 6,
            'year' => 2026,
        ]);

        $slotIds = [];
        for ($i = 0; $i < 210; $i++) {
            $day = intdiv($i, 7) + 1;
            $period = ($i % 7) + 1;
            $slotIds[] = $this->seedScheduleSlot($context['monthly_schedule_id'], $context['class_id'], [
                'subjectId' => $subjectId,
                'subject' => 'SUB-D1',
                'teacherId' => null,
                'roomId' => null,
                'subjectLessonId' => null,
                'content' => null,
                'note' => null,
                'slotStatus' => 'planned',
                'date' => sprintf('2026-06-%02d 07:00:00', $day),
                'periodNumber' => $period,
            ]);
        }

        $changes = [];
        foreach ($slotIds as $index => $slotId) {
            $changes[] = [
                'slot_id' => $slotId,
                'teacher_id' => $teacherId,
                'subject_lesson_id' => $lessonId,
                'room_id' => $roomId,
                'content' => 'Content ' . ($index + 1),
                'note' => 'Note ' . ($index + 1),
            ];
        }

        $response = $this->actingAs($user)->postJson(
            route('monthly-schedule.assignment.save', $context['monthly_schedule_id']),
            ['changes' => $changes]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('updated_slot_ids', $slotIds);
        $response->assertJsonPath('message', 'Da luu phan cong lich giang day theo thang thanh cong. Cap nhat 210 tiet.');

        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotIds[0],
            'monthly_schedule_id' => $context['monthly_schedule_id'],
            'teacher_id' => $teacherId,
            'room_id' => $roomId,
            'subject_lesson_id' => $lessonId,
            'content' => 'Content 1',
            'note' => 'Note 1',
        ]);
    }

    public function test_it_rejects_an_unknown_slot_id_and_a_slot_from_another_monthly_schedule(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $departmentId = $this->seedDepartment('D2');
        $teacherId = $this->seedTeacher($departmentId, 'Teacher D2', 'GV-D2');
        $roomId = $this->seedRoom('R2', 'Room 2');
        $subjectId = $this->seedSubject($departmentId, 'SUB-D2');
        $lessonId = $this->seedSubjectLesson($subjectId, 2, 'Lesson 2');
        $user = $this->seedDepartmentStaff($departmentId);

        $anchor = $this->seedMonthlyScheduleContext([
            'label' => 'anchor',
            'departmentId' => $departmentId,
            'month' => 6,
            'year' => 2026,
        ]);
        $anchorSlotId = $this->seedScheduleSlot($anchor['monthly_schedule_id'], $anchor['class_id'], [
            'subjectId' => $subjectId,
            'subject' => 'SUB-D2',
            'teacherId' => null,
            'roomId' => null,
            'subjectLessonId' => null,
            'content' => null,
            'note' => null,
            'slotStatus' => 'planned',
            'date' => '2026-06-15 07:00:00',
            'periodNumber' => 1,
        ]);

        $other = $this->seedMonthlyScheduleContext([
            'label' => 'other',
            'departmentId' => $departmentId,
            'month' => 6,
            'year' => 2026,
        ]);
        $otherSlotId = $this->seedScheduleSlot($other['monthly_schedule_id'], $other['class_id'], [
            'subjectId' => $subjectId,
            'subject' => 'SUB-D2',
            'teacherId' => null,
            'roomId' => null,
            'subjectLessonId' => null,
            'content' => null,
            'note' => null,
            'slotStatus' => 'planned',
            'date' => '2026-06-16 07:00:00',
            'periodNumber' => 1,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('monthly-schedule.assignment.save', $anchor['monthly_schedule_id']),
            [
                'changes' => [
                    [
                        'slot_id' => $anchorSlotId,
                        'teacher_id' => $teacherId,
                        'subject_lesson_id' => $lessonId,
                        'room_id' => $roomId,
                        'content' => 'Updated content',
                        'note' => 'Updated note',
                    ],
                    [
                        'slot_id' => $otherSlotId,
                        'teacher_id' => $teacherId,
                        'subject_lesson_id' => $lessonId,
                        'room_id' => $roomId,
                        'content' => 'Other schedule content',
                        'note' => 'Other schedule note',
                    ],
                    [
                        'slot_id' => 9999999,
                        'teacher_id' => $teacherId,
                        'subject_lesson_id' => $lessonId,
                        'room_id' => $roomId,
                        'content' => 'Missing slot',
                        'note' => 'Missing slot',
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['changes.2.slot_id']);
        $response->assertJsonMissingValidationErrors(['changes.0.slot_id', 'changes.1.slot_id']);
        $this->assertDatabaseMissing('schedule_slots', [
            'id' => $anchorSlotId,
            'teacher_id' => $teacherId,
            'room_id' => $roomId,
            'subject_lesson_id' => $lessonId,
        ]);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $otherSlotId,
            'monthly_schedule_id' => $other['monthly_schedule_id'],
            'teacher_id' => null,
            'room_id' => null,
            'subject_lesson_id' => null,
        ]);
    }

    public function test_it_returns_validation_errors_for_an_invalid_json_payload(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $departmentId = $this->seedDepartment('D3');
        $teacherId = $this->seedTeacher($departmentId, 'Teacher D3', 'GV-D3');
        $roomId = $this->seedRoom('R3', 'Room 3');
        $subjectId = $this->seedSubject($departmentId, 'SUB-D3');
        $lessonId = $this->seedSubjectLesson($subjectId, 3, 'Lesson 3');
        $user = $this->seedDepartmentStaff($departmentId);

        $context = $this->seedMonthlyScheduleContext([
            'label' => 'invalid',
            'departmentId' => $departmentId,
            'month' => 6,
            'year' => 2026,
        ]);
        $slotId = $this->seedScheduleSlot($context['monthly_schedule_id'], $context['class_id'], [
            'subjectId' => $subjectId,
            'subject' => 'SUB-D3',
            'teacherId' => null,
            'roomId' => null,
            'subjectLessonId' => null,
            'content' => null,
            'note' => null,
            'slotStatus' => 'planned',
            'date' => '2026-06-17 07:00:00',
            'periodNumber' => 2,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('monthly-schedule.assignment.save', $context['monthly_schedule_id']),
            [
                'changes' => [
                    [
                        'slot_id' => $slotId,
                        'teacher_id' => 'abc',
                        'subject_lesson_id' => $lessonId,
                        'room_id' => $roomId,
                        'content' => str_repeat('x', 501),
                        'note' => 'Note',
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['changes.0.teacher_id', 'changes.0.content']);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotId,
            'teacher_id' => null,
            'room_id' => null,
            'subject_lesson_id' => null,
        ]);
    }

    public function test_it_allows_clearing_teacher_and_room_assignments_via_json(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $departmentId = $this->seedDepartment('D4');
        $teacherId = $this->seedTeacher($departmentId, 'Teacher D4', 'GV-D4');
        $roomId = $this->seedRoom('R4', 'Room 4');
        $subjectId = $this->seedSubject($departmentId, 'SUB-D4');
        $lessonId = $this->seedSubjectLesson($subjectId, 4, 'Lesson 4');
        $user = $this->seedDepartmentStaff($departmentId);

        $context = $this->seedMonthlyScheduleContext([
            'label' => 'clear',
            'departmentId' => $departmentId,
            'month' => 6,
            'year' => 2026,
        ]);
        $slotId = $this->seedScheduleSlot($context['monthly_schedule_id'], $context['class_id'], [
            'subjectId' => $subjectId,
            'subject' => 'SUB-D4',
            'teacherId' => $teacherId,
            'roomId' => $roomId,
            'subjectLessonId' => $lessonId,
            'content' => 'Existing content',
            'note' => 'Existing note',
            'slotStatus' => 'planned',
            'date' => '2026-06-18 07:00:00',
            'periodNumber' => 3,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('monthly-schedule.assignment.save', $context['monthly_schedule_id']),
            [
                'changes' => [
                    [
                        'slot_id' => $slotId,
                        'teacher_id' => null,
                        'subject_lesson_id' => $lessonId,
                        'room_id' => null,
                        'content' => 'Existing content',
                        'note' => 'Existing note',
                    ],
                ],
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotId,
            'teacher_id' => null,
            'room_id' => null,
            'subject_lesson_id' => $lessonId,
            'content' => 'Existing content',
            'note' => 'Existing note',
        ]);
    }

    public function test_it_blocks_saves_when_the_current_batch_is_locked(): void
    {
        Carbon::setTestNow('2026-06-27 08:00:00');

        $departmentId = $this->seedDepartment('D5');
        $teacherId = $this->seedTeacher($departmentId, 'Teacher D5', 'GV-D5');
        $roomId = $this->seedRoom('R5', 'Room 5');
        $subjectId = $this->seedSubject($departmentId, 'SUB-D5');
        $lessonId = $this->seedSubjectLesson($subjectId, 5, 'Lesson 5');
        $user = $this->seedDepartmentStaff($departmentId);

        $context = $this->seedMonthlyScheduleContext([
            'label' => 'locked',
            'departmentId' => $departmentId,
            'month' => 6,
            'year' => 2026,
        ]);
        $slotId = $this->seedScheduleSlot($context['monthly_schedule_id'], $context['class_id'], [
            'subjectId' => $subjectId,
            'subject' => 'SUB-D5',
            'teacherId' => null,
            'roomId' => null,
            'subjectLessonId' => null,
            'content' => null,
            'note' => null,
            'slotStatus' => 'planned',
            'date' => '2026-06-19 07:00:00',
            'periodNumber' => 4,
        ]);

        DB::table('department_monthly_assignment_batches')->insert([
            'department_id' => $departmentId,
            'month' => 6,
            'year' => 2026,
            'status' => 'submitted',
            'review_note' => null,
            'submitted_by' => null,
            'reviewed_by' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(
            route('monthly-schedule.assignment.save', $context['monthly_schedule_id']),
            [
                'changes' => [
                    [
                        'slot_id' => $slotId,
                        'teacher_id' => $teacherId,
                        'subject_lesson_id' => $lessonId,
                        'room_id' => $roomId,
                        'content' => 'Locked content',
                        'note' => 'Locked note',
                    ],
                ],
            ]
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotId,
            'teacher_id' => null,
            'room_id' => null,
        ]);
    }

    private function seedDepartmentStaff(int $departmentId): User
    {
        return User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
            'department_id' => $departmentId,
        ]);
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

    private function seedTeacher(int $departmentId, string $name, string $code): int
    {
        $timestamp = now();

        return DB::table('teachers')->insertGetId([
            'teacher_code' => $code,
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

    private function seedSubjectLesson(int $subjectId, int $lessonNo, string $title): int
    {
        $timestamp = now();

        return DB::table('subject_lessons')->insertGetId([
            'subject_id' => $subjectId,
            'lesson_no' => $lessonNo,
            'title' => $title,
            'expected_periods' => null,
            'note' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * @param array{
     *     label:string,
     *     departmentId:int,
     *     month:int,
     *     year:int
     * } $data
     *
     * @return array{monthly_schedule_id:int,class_id:int,plan_id:int}
     */
    private function seedMonthlyScheduleContext(array $data): array
    {
        $timestamp = now();
        $label = $data['label'] ?? 'slot';

        $classId = DB::table('classes')->insertGetId([
            'code' => 'CLS-' . $label . '-' . uniqid(),
            'name' => 'Class ' . $label . '-' . uniqid(),
            'course_year' => 2026,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'training_batch_id' => null,
            'name' => 'Plan ' . $label . '-' . uniqid(),
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

        return [
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $classId,
            'plan_id' => $planId,
        ];
    }

    /**
     * @param array{
     *     subjectId:int,
     *     subject:string,
     *     teacherId:?int,
     *     roomId:?int,
     *     subjectLessonId:?int,
     *     content:?string,
     *     note:?string,
     *     slotStatus:string,
     *     date:string,
     *     periodNumber:int
     * } $data
     */
    private function seedScheduleSlot(int $monthlyScheduleId, int $classId, array $data): int
    {
        $timestamp = now();

        return DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => null,
            'class_id' => $classId,
            'teacher_id' => $data['teacherId'],
            'subject_id' => $data['subjectId'],
            'subject_lesson_id' => $data['subjectLessonId'],
            'room_id' => $data['roomId'],
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => $data['date'],
            'day_of_week' => 1,
            'period' => (string) $data['periodNumber'],
            'period_number' => $data['periodNumber'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'slot_status' => $data['slotStatus'],
            'actual_content' => null,
            'note' => $data['note'],
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
