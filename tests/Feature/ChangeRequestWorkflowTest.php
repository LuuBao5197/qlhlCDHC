<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Application\Shared\ChangeRequestPageDataBuilder;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Schedule\Models\TeachingSupportRequestItem;
use Modules\Training\Models\ChangeRequest;
use Modules\Training\Models\Department;
use Modules\Training\Models\Room;
use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Teacher;
use Modules\Training\Models\TrainingClass;
use Tests\TestCase;

class ChangeRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_staff_only_sees_and_submits_own_department_slots(): void
    {
        $departmentA = Department::factory()->create([
            'code' => 'KHOA-A',
            'name' => 'Khoa A',
            'status' => 'active',
        ]);

        $departmentB = Department::factory()->create([
            'code' => 'KHOA-B',
            'name' => 'Khoa B',
            'status' => 'active',
        ]);

        $departmentStaff = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
            'department_id' => $departmentA->id,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan department scope test',
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => 'Plan test',
            'status' => 'approved',
            'current_step' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => 8,
            'year' => 2026,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $class = TrainingClass::factory()->create([
            'code' => 'CLS-SCOPE-01',
            'name' => 'Scope class',
            'status' => 'active',
        ]);

        $teacher = Teacher::factory()->create([
            'department_id' => $departmentA->id,
            'teacher_code' => 'GV-SCOPE',
            'name' => 'Giang vien scope',
            'status' => 'active',
        ]);

        $room = Room::factory()->create([
            'code' => 'RM-SCOPE',
            'name' => 'Phong scope',
            'status' => 'active',
        ]);

        $subjectA = Subject::factory()->create([
            'department_id' => $departmentA->id,
            'code' => 'MON-A',
            'name' => 'Mon A',
        ]);

        $subjectB = Subject::factory()->create([
            'department_id' => $departmentB->id,
            'code' => 'MON-B',
            'name' => 'Mon B',
        ]);

        $lessonA = SubjectLesson::factory()->create([
            'subject_id' => $subjectA->id,
            'lesson_no' => 1,
            'title' => 'Bai hoc A',
        ]);

        $lessonB = SubjectLesson::factory()->create([
            'subject_id' => $subjectB->id,
            'lesson_no' => 1,
            'title' => 'Bai hoc B',
        ]);

        $slotAId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subjectA->id,
            'subject_lesson_id' => $lessonA->id,
            'room_id' => $room->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-10 00:00:00',
            'day_of_week' => 2,
            'period' => 'Sang',
            'period_number' => 1,
            'subject' => 'Mon A',
            'content' => 'Noi dung A',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supportSlotId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subjectA->id,
            'subject_lesson_id' => $lessonA->id,
            'room_id' => $room->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-10 00:00:00',
            'day_of_week' => 2,
            'period' => 'Sang',
            'period_number' => 2,
            'subject' => 'Mon A',
            'content' => 'Noi dung support',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supportRequestId = DB::table('teaching_support_requests')->insertGetId([
            'assignment_batch_id' => DB::table('department_monthly_assignment_batches')->insertGetId([
                'department_id' => $departmentA->id,
                'month' => 8,
                'year' => 2026,
                'status' => 'draft',
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'requesting_department_id' => $departmentA->id,
            'proposed_supporting_department_id' => $departmentB->id,
            'assigned_supporting_department_id' => null,
            'status' => TeachingSupportRequest::STATUS_PENDING_PDT,
            'request_note' => 'Support test',
            'submitted_by' => $departmentStaff->id,
            'submitted_at' => now(),
            'pdt_processed_by' => null,
            'pdt_processed_at' => null,
            'pdt_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teaching_support_request_items')->insert([
            'request_id' => $supportRequestId,
            'schedule_slot_id' => $supportSlotId,
            'status' => TeachingSupportRequestItem::STATUS_PENDING,
            'assigned_teacher_id' => null,
            'assigned_by' => null,
            'assigned_at' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotBId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subjectB->id,
            'subject_lesson_id' => $lessonB->id,
            'room_id' => $room->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-10 00:00:00',
            'day_of_week' => 2,
            'period' => 'Chieu',
            'period_number' => 2,
            'subject' => 'Mon B',
            'content' => 'Noi dung B',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pageData = app(ChangeRequestPageDataBuilder::class)->build($departmentStaff);
        $this->assertCount(1, $pageData['monthlySchedules']);
        $this->assertCount(1, $pageData['changeRequestMonthlySchedules']);
        $this->assertCount(1, $pageData['changeRequestMonthlySchedules']->first()->scheduleSlots);
        $this->assertSame($slotAId, $pageData['changeRequestMonthlySchedules']->first()->scheduleSlots->first()->id);
        $this->assertNotContains($supportSlotId, $pageData['changeRequestMonthlySchedules']->first()->scheduleSlots->pluck('id')->all());

        $payload = [
            [
                'slot_id' => $slotAId,
                'new_payload' => [
                    'room_id' => $room->id,
                    'content' => 'Noi dung A moi',
                ],
            ],
            [
                'slot_id' => $supportSlotId,
                'new_payload' => [
                    'room_id' => $room->id,
                    'content' => 'Noi dung support moi',
                ],
            ],
        ];

        $response = $this
            ->actingAs($departmentStaff)
            ->from('/change-requests/create')
            ->post('/change-requests', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'reason' => 'Test scope khoa',
                'apply_mode' => 'all_or_none',
                'selected_slots_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

        $response->assertRedirect('/change-requests/create');
        $response->assertSessionHasErrors(['selected_slots.1.slot_id']);
        $this->assertDatabaseCount('change_requests', 0);
    }

    public function test_change_request_auto_expands_active_merge_group_into_all_group_slots(): void
    {
        $departmentStaff = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan merge group change request test',
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => 'Plan test',
            'status' => 'approved',
            'current_step' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => 8,
            'year' => 2026,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classA = TrainingClass::factory()->create([
            'code' => 'CLS-GRP-01',
            'name' => 'Group class 1',
            'status' => 'active',
        ]);

        $classB = TrainingClass::factory()->create([
            'code' => 'CLS-GRP-02',
            'name' => 'Group class 2',
            'status' => 'active',
        ]);

        $teacher = Teacher::factory()->create([
            'teacher_code' => 'GV-GRP',
            'name' => 'Giang vien group',
            'status' => 'active',
        ]);

        $subject = Subject::factory()->create([
            'code' => 'SUB-GRP',
            'name' => 'Mon group',
        ]);

        $lesson = SubjectLesson::factory()->create([
            'subject_id' => $subject->id,
            'lesson_no' => 1,
            'title' => 'Bai hoc group',
        ]);

        $room = Room::factory()->create([
            'code' => 'RM-GRP',
            'name' => 'Phong group',
            'status' => 'active',
        ]);

        $groupId = DB::table('schedule_slot_groups')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'date' => '2026-08-11',
            'period_number' => 3,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'status' => 'active',
            'note' => null,
            'created_by' => $departmentStaff->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotOneId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => $groupId,
            'class_id' => $classA->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'room_id' => $room->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-11 00:00:00',
            'day_of_week' => 2,
            'period' => 'Sang',
            'period_number' => 3,
            'subject' => 'Mon group',
            'content' => 'Noi dung group 1',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotTwoId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => $groupId,
            'class_id' => $classB->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'room_id' => $room->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-11 00:00:00',
            'day_of_week' => 2,
            'period' => 'Sang',
            'period_number' => 3,
            'subject' => 'Mon group',
            'content' => 'Noi dung group 2',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($departmentStaff)
            ->from('/change-requests/create')
            ->post('/change-requests', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'reason' => 'Test auto expand group',
                'apply_mode' => 'all_or_none',
                'selected_slots_json' => json_encode([
                    [
                        'slot_id' => $slotOneId,
                        'new_payload' => [
                            'room_id' => $room->id,
                            'content' => 'Noi dung group moi',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('change_requests', 1);
        $this->assertDatabaseHas('change_request_items', [
            'schedule_slot_id' => $slotOneId,
        ]);
        $this->assertDatabaseHas('change_request_items', [
            'schedule_slot_id' => $slotTwoId,
        ]);
    }

    public function test_review_rejects_partial_active_merge_group_before_apply(): void
    {
        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan review merge group test',
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => 'Plan test',
            'status' => 'approved',
            'current_step' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => 8,
            'year' => 2026,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classA = TrainingClass::factory()->create([
            'code' => 'CLS-RVW-01',
            'name' => 'Review class 1',
            'status' => 'active',
        ]);

        $classB = TrainingClass::factory()->create([
            'code' => 'CLS-RVW-02',
            'name' => 'Review class 2',
            'status' => 'active',
        ]);

        $teacher = Teacher::factory()->create([
            'teacher_code' => 'GV-RVW',
            'name' => 'Giang vien review',
            'status' => 'active',
        ]);

        $subject = Subject::factory()->create([
            'code' => 'SUB-RVW',
            'name' => 'Mon review',
        ]);

        $lesson = SubjectLesson::factory()->create([
            'subject_id' => $subject->id,
            'lesson_no' => 1,
            'title' => 'Bai hoc review',
        ]);

        $room = Room::factory()->create([
            'code' => 'RM-RVW',
            'name' => 'Phong review',
            'status' => 'active',
        ]);

        $groupId = DB::table('schedule_slot_groups')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'date' => '2026-08-12',
            'period_number' => 4,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'status' => 'active',
            'note' => null,
            'created_by' => $trainingOffice->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotOneId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => $groupId,
            'class_id' => $classA->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'room_id' => $room->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-12 00:00:00',
            'day_of_week' => 3,
            'period' => 'Sang',
            'period_number' => 4,
            'subject' => 'Mon review',
            'content' => 'Noi dung review 1',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => $groupId,
            'class_id' => $classB->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'room_id' => $room->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-12 00:00:00',
            'day_of_week' => 3,
            'period' => 'Sang',
            'period_number' => 4,
            'subject' => 'Mon review',
            'content' => 'Noi dung review 2',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $changeRequestId = DB::table('change_requests')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_id' => $slotOneId,
            'requested_by' => $trainingOffice->id,
            'reason' => 'Partial group review test',
            'old_payload' => json_encode([
                'class_id' => $classA->id,
                'teacher_id' => $teacher->id,
                'assignment_type' => null,
                'subject_id' => $subject->id,
                'subject_lesson_id' => $lesson->id,
                'room_id' => $room->id,
                'date' => '2026-08-12',
                'day_of_week' => 3,
                'period' => 'Sang',
                'period_number' => 4,
                'subject' => 'Mon review',
                'content' => 'Noi dung review 1',
                'slot_status' => 'planned',
                'actual_content' => null,
                'note' => null,
            ], JSON_UNESCAPED_UNICODE),
            'new_payload' => json_encode([
                'room_id' => $room->id,
                'content' => 'Noi dung review moi',
            ], JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'change_type' => 'general',
            'apply_mode' => 'all_or_none',
            'apply_changes' => 1,
            'apply_summary' => null,
            'submitted_at' => now(),
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('change_request_items')->insert([
            [
                'change_request_id' => $changeRequestId,
                'schedule_slot_id' => $slotOneId,
                'old_payload' => json_encode([
                    'class_id' => $classA->id,
                    'teacher_id' => $teacher->id,
                    'assignment_type' => null,
                    'subject_id' => $subject->id,
                    'subject_lesson_id' => $lesson->id,
                    'room_id' => $room->id,
                    'date' => '2026-08-12',
                    'day_of_week' => 3,
                    'period' => 'Sang',
                    'period_number' => 4,
                    'subject' => 'Mon review',
                    'content' => 'Noi dung review 1',
                    'slot_status' => 'planned',
                    'actual_content' => null,
                    'note' => null,
                ], JSON_UNESCAPED_UNICODE),
                'new_payload' => json_encode([
                    'room_id' => $room->id,
                    'content' => 'Noi dung review moi',
                ], JSON_UNESCAPED_UNICODE),
                'apply_status' => null,
                'apply_error' => null,
                'applied_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($trainingOffice)
            ->post(route('change-request.review', $changeRequestId), [
                'action' => 'approve',
                'comment' => 'Review partial group',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequestId,
            'status' => 'pending',
        ]);
    }

    public function test_batch_change_request_applies_self_study_rules_on_review(): void
    {
        $departmentStaff = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
        ]);

        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan batch change request test',
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => 'Plan test',
            'status' => 'approved',
            'current_step' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => 8,
            'year' => 2026,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $class = TrainingClass::factory()->create([
            'code' => 'CLS-BATCH-01',
            'name' => 'Batch class',
            'status' => 'active',
        ]);

        $teacher = Teacher::factory()->create([
            'teacher_code' => 'GV-01',
            'name' => 'Giang vien 01',
            'status' => 'active',
        ]);

        $subject = Subject::factory()->create([
            'code' => 'SUB-01',
            'name' => 'Mon 01',
        ]);

        $lesson = SubjectLesson::factory()->create([
            'subject_id' => $subject->id,
            'lesson_no' => 1,
            'title' => 'Bai hoc 1',
        ]);

        $roomA = Room::factory()->create([
            'code' => 'RM-A',
            'name' => 'Phong A',
            'status' => 'active',
        ]);

        $roomB = Room::factory()->create([
            'code' => 'RM-B',
            'name' => 'Phong B',
            'status' => 'active',
        ]);

        $slotOneId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'room_id' => $roomA->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-10 00:00:00',
            'day_of_week' => 2,
            'period' => 'Sang',
            'period_number' => 1,
            'subject' => 'Mon 01',
            'content' => 'Noi dung cu',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotTwoId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'assignment_type' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subject->id,
            'subject_lesson_id' => $lesson->id,
            'room_id' => $roomA->id,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-08-10 00:00:00',
            'day_of_week' => 2,
            'period' => 'Chieu',
            'period_number' => 2,
            'subject' => 'Mon 01',
            'content' => 'Noi dung cu',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            [
                'slot_id' => $slotOneId,
                'new_payload' => [
                    'assignment_type' => ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY,
                    'teacher_id' => null,
                    'subject_lesson_id' => null,
                ],
            ],
            [
                'slot_id' => $slotTwoId,
                'new_payload' => [
                    'room_id' => $roomB->id,
                    'content' => 'Noi dung moi',
                ],
            ],
        ];

        $createResponse = $this
            ->actingAs($departmentStaff)
            ->from('/schedules')
            ->post('/change-requests', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'reason' => 'Batch change request',
                'apply_mode' => 'all_or_none',
                'selected_slots_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

        $createResponse->assertRedirect('/schedules');

        $changeRequest = ChangeRequest::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('pending', $changeRequest->status);
        $this->assertSame('all_or_none', $changeRequest->apply_mode);
        $this->assertCount(2, $changeRequest->changeRequestItems);

        $reviewResponse = $this
            ->actingAs($trainingOffice)
            ->from('/schedules')
            ->post('/change-requests/' . $changeRequest->id . '/review', [
                'action' => 'approve',
                'apply_changes' => 1,
                'apply_mode' => 'all_or_none',
                'comment' => 'Approve batch',
            ]);

        $reviewResponse->assertRedirect('/schedules');

        $slotOne = ScheduleSlot::query()->findOrFail($slotOneId);
        $this->assertSame(ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY, $slotOne->assignment_type);
        $this->assertNull($slotOne->teacher_id);
        $this->assertNull($slotOne->subject_lesson_id);

        $slotTwo = ScheduleSlot::query()->findOrFail($slotTwoId);
        $this->assertSame($roomB->id, (int) $slotTwo->room_id);
        $this->assertSame('Noi dung moi', $slotTwo->content);

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'approved',
        ]);
    }
}
