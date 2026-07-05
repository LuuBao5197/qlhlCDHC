<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Schedule\Models\TeachingSupportRequestItem;
use Tests\Support\TestingDatabaseGuard;
use Tests\TestCase;

class TeachingSupportRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_department_staff_can_create_support_request_without_submitting_batch(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $userA = $this->seedDepartmentStaff($departmentAId);

        $response = $this->actingAs($userA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can giang vien thay the',
                'slot_ids' => $slotIds,
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('teaching_support_requests', [
            'requesting_department_id' => $departmentAId,
            'proposed_supporting_department_id' => $departmentBId,
            'status' => TeachingSupportRequest::STATUS_PENDING_PDT,
        ]);
        $this->assertDatabaseHas('department_monthly_assignment_batches', [
            'department_id' => $departmentAId,
            'month' => 7,
            'year' => 2026,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('teaching_support_request_items', [
            'schedule_slot_id' => $slotIds[0],
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotIds[0],
            'assignment_source' => 'internal',
            'teacher_id' => null,
        ]);
    }

    public function test_support_request_submit_requires_support_department_and_slot_list(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $userA = $this->seedDepartmentStaff($departmentAId);

        $response = $this->actingAs($userA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'request_note' => 'Can ho tro',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'supporting_department_id',
            'slot_ids',
        ]);

        $this->assertDatabaseMissing('teaching_support_requests', [
            'requesting_department_id' => $departmentAId,
            'proposed_supporting_department_id' => $departmentBId,
        ]);
        $this->assertDatabaseMissing('teaching_support_request_items', [
            'schedule_slot_id' => $slotIds[0],
        ]);
    }

    public function test_support_request_submit_rejects_mismatched_requesting_department_id(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $userA = $this->seedDepartmentStaff($departmentAId);

        $response = $this->actingAs($userA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'requesting_department_id' => $departmentBId,
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['requesting_department_id']);
    }

    public function test_duplicate_request_is_blocked_when_slot_is_already_in_active_support_workflow(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $userA = $this->seedDepartmentStaff($departmentAId);

        $this->actingAs($userA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Lan 1',
                'slot_ids' => $slotIds,
            ]
        )->assertOk();

        $duplicateResponse = $this->actingAs($userA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Lan 2',
                'slot_ids' => $slotIds,
            ]
        );

        $duplicateResponse->assertStatus(422);
        $duplicateResponse->assertJsonValidationErrors(['slot_ids']);
    }

    public function test_pdt_can_assign_department_and_support_department_can_confirm_teacher_assignments(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);

        $requestResponse = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        );

        $requestId = (int) $requestResponse->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $this->actingAs($staffB)->postJson(route('teaching-support-requests.confirm', $requestId), [
            'assignments' => [
                [
                    'request_item_id' => DB::table('teaching_support_request_items')->where('request_id', $requestId)->first()->id,
                    'teacher_id' => $teacherBId,
                    'note' => 'GV khoa B',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('teaching_support_requests', [
            'id' => $requestId,
            'status' => TeachingSupportRequest::STATUS_COMPLETED,
            'assigned_supporting_department_id' => $departmentBId,
        ]);
        $this->assertDatabaseHas('teaching_support_request_items', [
            'request_id' => $requestId,
            'assigned_teacher_id' => $teacherBId,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotIds[0],
            'teacher_id' => $teacherBId,
            'assignment_source' => 'department_support',
        ]);
    }

    public function test_internal_assignment_save_is_blocked_when_support_request_exists(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $supportTeacher = $this->seedTeacher($departmentBId, 'Teacher B', 'GV-B');

        $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        )->assertOk();

        $response = $this->actingAs($staffA)->postJson(
            route('monthly-schedule.assignment.save', $monthlyScheduleId),
            [
                'changes' => [
                    [
                        'slot_id' => $slotIds[0],
                        'teacher_id' => $supportTeacher,
                        'subject_lesson_id' => null,
                        'room_id' => null,
                        'content' => 'Noi dung moi',
                        'note' => 'Ghi chu moi',
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['changes.0.slot_id']);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotIds[0],
            'teacher_id' => null,
            'assignment_source' => 'internal',
        ]);
    }

    public function test_event_slots_are_rejected_from_support_requests(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext(withEventSlot: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);

        $response = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slot_ids']);
    }

    public function test_department_b_assignment_page_shows_confirmed_support_workload_as_read_only_tab(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);

        $requestResponse = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        );

        $requestId = (int) $requestResponse->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $this->actingAs($staffB)->postJson(route('teaching-support-requests.confirm', $requestId), [
            'assignments' => [
                [
                    'request_item_id' => DB::table('teaching_support_request_items')->where('request_id', $requestId)->first()->id,
                    'teacher_id' => $teacherBId,
                    'note' => 'GV khoa B',
                ],
            ],
        ])->assertOk();

        $bSchedule = $this->seedDepartmentMonthlyScheduleContext(
            $departmentBId,
            'B',
            'SUB-B',
            '2026-07-12 07:00:00',
            1
        );

        $response = $this->actingAs($staffB)->get(route('monthly-schedule.assignment', $bSchedule['monthly_schedule_id']));

        $response->assertOk();
        $response->assertSee('Nhiệm vụ hỗ trợ liên khoa');
        $response->assertSee('Department A');
        $response->assertSee('Teacher B');
        $this->assertSame(1, (int) DB::table('schedule_slots')->where('assignment_source', 'department_support')->count());
    }

    public function test_department_b_assignment_page_shows_inline_support_assignment_controls(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);

        $requestResponse = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        );

        $requestId = (int) $requestResponse->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $bSchedule = $this->seedDepartmentMonthlyScheduleContext(
            $departmentBId,
            'B',
            'SUB-B',
            '2026-07-12 07:00:00',
            1
        );

        $response = $this->actingAs($staffB)->get(route('monthly-schedule.assignment', $bSchedule['monthly_schedule_id']));

        $response->assertOk();
        $response->assertSee('support-teacher-select');
        $response->assertSee('support-save-btn');
        $response->assertSee('name="teacher_id"', false);
    }

    public function test_department_b_can_assign_support_teacher_inline_from_monthly_assignment_page(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);

        $requestResponse = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        );

        $requestId = (int) $requestResponse->json('request_id');
        $requestItemId = (int) DB::table('teaching_support_request_items')->where('request_id', $requestId)->value('id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $response = $this->actingAs($staffB)->postJson(route('teaching-support-request-items.assign', $requestItemId), [
            'teacher_id' => $teacherBId,
            'note' => 'Phan cong inline',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('teaching_support_request_items', [
            'id' => $requestItemId,
            'assigned_teacher_id' => $teacherBId,
            'status' => TeachingSupportRequestItem::STATUS_CONFIRMED,
        ]);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotIds[0],
            'teacher_id' => $teacherBId,
            'assignment_source' => 'department_support',
            'teaching_support_request_item_id' => $requestItemId,
        ]);
        $this->assertDatabaseHas('teaching_support_requests', [
            'id' => $requestId,
            'status' => TeachingSupportRequest::STATUS_COMPLETED,
        ]);
    }

    public function test_support_confirmation_blocks_teacher_conflict_with_internal_slot(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);

        $this->seedDepartmentMonthlyScheduleContext(
            $departmentBId,
            'B',
            'SUB-B',
            '2026-07-10 07:00:00',
            1,
            teacherId: $teacherBId
        );

        $requestResponse = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => $slotIds,
            ]
        );

        $requestId = (int) $requestResponse->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $response = $this->actingAs($staffB)->postJson(route('teaching-support-requests.confirm', $requestId), [
            'assignments' => [
                [
                    'request_item_id' => DB::table('teaching_support_request_items')->where('request_id', $requestId)->first()->id,
                    'teacher_id' => $teacherBId,
                    'note' => 'GV khoa B',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assignments.0.teacher_id']);
        $this->assertDatabaseHas('teaching_support_request_items', [
            'request_id' => $requestId,
            'assigned_teacher_id' => null,
            'status' => TeachingSupportRequestItem::STATUS_PENDING,
        ]);
    }

    public function test_support_confirmation_blocks_teacher_conflict_with_existing_support_slot(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);

        $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Lan 1',
                'slot_ids' => $slotIds,
            ]
        )->assertOk();

        $firstRequestId = (int) DB::table('teaching_support_requests')->orderBy('id')->value('id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $firstRequestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $this->actingAs($staffB)->postJson(route('teaching-support-requests.confirm', $firstRequestId), [
            'assignments' => [
                [
                    'request_item_id' => DB::table('teaching_support_request_items')->where('request_id', $firstRequestId)->first()->id,
                    'teacher_id' => $teacherBId,
                    'note' => 'GV khoa B',
                ],
            ],
        ])->assertOk();

        $secondSlotId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => null,
            'class_id' => DB::table('schedule_slots')->whereKey($slotIds[0])->value('class_id'),
            'teacher_id' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => DB::table('schedule_slots')->whereKey($slotIds[0])->value('subject_id'),
            'subject_lesson_id' => null,
            'room_id' => DB::table('schedule_slots')->whereKey($slotIds[0])->value('room_id'),
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-07-10 07:00:00',
            'day_of_week' => 5,
            'period' => '1',
            'period_number' => 1,
            'subject' => 'SUB-A',
            'content' => null,
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondRequestResponse = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $monthlyScheduleId),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Lan 2',
                'slot_ids' => [$secondSlotId],
            ]
        );

        $secondRequestId = (int) $secondRequestResponse->json('request_id');
        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $secondRequestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $response = $this->actingAs($staffB)->postJson(route('teaching-support-requests.confirm', $secondRequestId), [
            'assignments' => [
                [
                    'request_item_id' => DB::table('teaching_support_request_items')->where('request_id', $secondRequestId)->first()->id,
                    'teacher_id' => $teacherBId,
                    'note' => 'GV khoa B',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assignments.0.teacher_id']);
    }

    public function test_support_confirmation_allows_same_active_merge_group_with_same_teacher(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);
        $activeGroupContext = $this->seedDepartmentMonthlyScheduleContext(
            $departmentAId,
            'AG',
            'SUB-AG',
            '2026-07-10 07:00:00',
            1,
            2,
            true
        );

        $requestResponse = $this->actingAs($staffA)->postJson(
            route('teaching-support-requests.store', $activeGroupContext['monthly_schedule_id']),
            [
                'supporting_department_id' => $departmentBId,
                'request_note' => 'Can ho tro',
                'slot_ids' => [$activeGroupContext['slot_ids'][0]],
            ]
        );

        $requestId = (int) $requestResponse->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $this->actingAs($staffB)->postJson(route('teaching-support-requests.confirm', $requestId), [
            'assignments' => [
                [
                    'request_item_id' => DB::table('teaching_support_request_items')->where('request_id', $requestId)->first()->id,
                    'teacher_id' => $teacherBId,
                    'note' => 'GV khoa B',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('schedule_slots', [
            'id' => $activeGroupContext['slot_ids'][0],
            'teacher_id' => $teacherBId,
            'assignment_source' => 'department_support',
        ]);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $activeGroupContext['slot_ids'][1],
            'teacher_id' => $teacherBId,
            'assignment_source' => 'department_support',
        ]);
    }

    public function test_department_a_can_withdraw_pending_request_directly(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $staffA = $this->seedDepartmentStaff($departmentAId);

        $requestResponse = $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Can ho tro',
            'slot_ids' => $slotIds,
        ]);

        $requestId = (int) $requestResponse->json('request_id');

        $withdrawResponse = $this->actingAs($staffA)->postJson(route('teaching-support-requests.withdraw', $requestId), [
            'reason' => 'Nhap sai thong tin',
        ]);

        $withdrawResponse->assertOk();
        $this->assertDatabaseHas('teaching_support_requests', [
            'id' => $requestId,
            'status' => TeachingSupportRequest::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('teaching_support_audit_logs', [
            'request_id' => $requestId,
            'action' => 'support_request_withdrawn',
        ]);
    }

    public function test_pdt_index_can_filter_cancelled_requests(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();

        $cancelledRequestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Don huy',
            'slot_ids' => $slotIds,
        ])->json('request_id');

        DB::table('teaching_support_requests')->where('id', $cancelledRequestId)->update([
            'status' => TeachingSupportRequest::STATUS_CANCELLED,
        ]);

        $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Cho PDT',
            'slot_ids' => [$slotIds[0]],
        ])->assertOk();

        $response = $this->actingAs($pdt)->get(route('teaching-support-requests.index', [
            'status' => TeachingSupportRequest::STATUS_CANCELLED,
        ]));

        $response->assertOk();
        $response->assertSee('Đã hủy');
        $response->assertSee('Xem chi tiết');
        $response->assertDontSee('Xem và phê duyệt');
    }

    public function test_pdt_detail_shows_review_actions_for_pending_request(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();

        $requestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Can PDT',
            'slot_ids' => $slotIds,
        ])->json('request_id');

        $response = $this->actingAs($pdt)->get(route('teaching-support-requests.show', $requestId));

        $response->assertOk();
        $response->assertSee('Phê duyệt và giao khoa');
        $response->assertSee('Trả về');
        $response->assertSee(route('teaching-support-requests.review', $requestId));
    }

    public function test_department_a_can_submit_cancellation_request_after_pdt_assignment(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();

        $requestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Can ho tro',
            'slot_ids' => $slotIds,
        ])->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $changeResponse = $this->actingAs($staffA)->postJson(route('teaching-support-change-requests.store', $requestId), [
            'reason' => 'Muon huy toan bo request',
        ]);

        $changeResponse->assertOk();
        $this->assertDatabaseHas('teaching_support_change_requests', [
            'teaching_support_request_id' => $requestId,
            'status' => 'pending_pdt',
        ]);
        $this->assertDatabaseHas('teaching_support_audit_logs', [
            'request_id' => $requestId,
            'action' => 'support_cancellation_request_submitted',
        ]);
    }

    public function test_pdt_approve_cancellation_releases_only_linked_support_slot(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId] = $this->seedSupportWorkflowContext(withTeacher: true);
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();
        $staffB = $this->seedDepartmentStaff($departmentBId);

        $requestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Can ho tro',
            'slot_ids' => $slotIds,
        ])->json('request_id');
        $itemId = (int) DB::table('teaching_support_request_items')->where('request_id', $requestId)->value('id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $this->actingAs($staffB)->postJson(route('teaching-support-request-items.assign', $itemId), [
            'teacher_id' => $teacherBId,
            'note' => 'Da phan cong',
        ])->assertOk();

        $changeRequestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-change-requests.store', $requestId), [
            'reason' => 'Can huy toan bo',
        ])->json('change_request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-change-requests.review', $changeRequestId), [
            'action' => 'approve',
            'pdt_note' => 'Chap nhan huy',
        ])->assertOk();

        $this->assertDatabaseHas('teaching_support_requests', [
            'id' => $requestId,
            'status' => TeachingSupportRequest::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('teaching_support_request_items', [
            'request_id' => $requestId,
            'status' => TeachingSupportRequestItem::STATUS_REJECTED,
            'assigned_teacher_id' => null,
        ]);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotIds[0],
            'teacher_id' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
        ]);
        $this->assertDatabaseHas('teaching_support_audit_logs', [
            'request_id' => $requestId,
            'action' => 'support_cancellation_request_applied',
        ]);
    }

    public function test_pdt_return_keeps_request_unchanged(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();

        $requestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Can ho tro',
            'slot_ids' => $slotIds,
        ])->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $changeRequestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-change-requests.store', $requestId), [
            'reason' => 'De nghi huy',
        ])->json('change_request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-change-requests.review', $changeRequestId), [
            'action' => 'reject',
            'pdt_note' => 'Tra ve de sua',
        ])->assertOk();

        $this->assertDatabaseHas('teaching_support_requests', [
            'id' => $requestId,
            'status' => TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
        ]);
        $this->assertDatabaseHas('teaching_support_change_requests', [
            'id' => $changeRequestId,
            'status' => 'returned',
        ]);
    }

    public function test_duplicate_active_cancellation_request_is_blocked(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds] = $this->seedSupportWorkflowContext();
        $staffA = $this->seedDepartmentStaff($departmentAId);
        $pdt = $this->seedPdtUser();

        $requestId = (int) $this->actingAs($staffA)->postJson(route('teaching-support-requests.store', $monthlyScheduleId), [
            'supporting_department_id' => $departmentBId,
            'request_note' => 'Can ho tro',
            'slot_ids' => $slotIds,
        ])->json('request_id');

        $this->actingAs($pdt)->postJson(route('teaching-support-requests.review', $requestId), [
            'action' => 'approve',
            'assigned_supporting_department_id' => $departmentBId,
            'pdt_note' => 'Giao khoa B',
        ])->assertOk();

        $this->actingAs($staffA)->postJson(route('teaching-support-change-requests.store', $requestId), [
            'reason' => 'Lan 1',
        ])->assertOk();

        $second = $this->actingAs($staffA)->postJson(route('teaching-support-change-requests.store', $requestId), [
            'reason' => 'Lan 2',
        ]);

        $second->assertStatus(422);
        $second->assertJsonValidationErrors(['change_request']);
    }

    private function seedDepartmentStaff(int $departmentId): User
    {
        return User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
            'department_id' => $departmentId,
        ]);
    }

    private function seedPdtUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
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

    private function seedSupportWorkflowContext(bool $withTeacher = false, bool $withEventSlot = false): array
    {
        $timestamp = now();
        $departmentAId = $this->seedDepartment('A');
        $departmentBId = $this->seedDepartment('B');
        $subjectId = $this->seedSubject($departmentAId, 'SUB-A');
        $classId = DB::table('classes')->insertGetId([
            'code' => 'CLS-A',
            'name' => 'Class A',
            'course_year' => 2026,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $planId = DB::table('plans')->insertGetId([
            'training_batch_id' => null,
            'name' => 'Plan A',
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => null,
            'created_by' => null,
            'submitted_by' => null,
            'submitted_at' => null,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => '2026-07-01',
            'effective_to' => '2026-07-31',
            'approved_version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => 7,
            'year' => 2026,
            'created_by' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'code' => 'RM-A',
            'name' => 'Room A',
            'capacity' => 40,
            'room_type' => 'classroom',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $slotIds = [];
        $slotIds[] = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_group_id' => null,
            'class_id' => $classId,
            'teacher_id' => null,
            'assignment_source' => 'internal',
            'teaching_support_request_item_id' => null,
            'subject_id' => $subjectId,
            'subject_lesson_id' => null,
            'room_id' => $roomId,
            'slot_type' => 'subject',
            'semester_event_id' => null,
            'event_type' => null,
            'date' => '2026-07-10 07:00:00',
            'day_of_week' => 5,
            'period' => '1',
            'period_number' => 1,
            'subject' => 'SUB-A',
            'content' => null,
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        if ($withEventSlot) {
            $slotIds[] = DB::table('schedule_slots')->insertGetId([
                'monthly_schedule_id' => $monthlyScheduleId,
                'schedule_slot_group_id' => null,
                'class_id' => $classId,
                'teacher_id' => null,
                'assignment_source' => 'internal',
                'teaching_support_request_item_id' => null,
                'subject_id' => null,
                'subject_lesson_id' => null,
                'room_id' => $roomId,
                'slot_type' => 'event',
                'semester_event_id' => null,
                'event_type' => 'holiday',
                'date' => '2026-07-11 07:00:00',
                'day_of_week' => 6,
                'period' => '2',
                'period_number' => 2,
                'subject' => 'Event',
                'content' => null,
                'slot_status' => 'planned',
                'actual_content' => null,
                'note' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $teacherBId = null;
        if ($withTeacher) {
            $teacherBId = $this->seedTeacher($departmentBId, 'Teacher B', 'GV-B');
        }

        return [$departmentAId, $departmentBId, $monthlyScheduleId, $slotIds, $teacherBId];
    }

    private function seedDepartmentMonthlyScheduleContext(
        int $departmentId,
        string $suffix,
        string $subjectCode,
        string $dateTime,
        int $periodNumber,
        int $slotCount = 1,
        bool $useActiveMergeGroup = false,
        ?int $teacherId = null
    ): array {
        $timestamp = now();
        $subjectId = $this->seedSubject($departmentId, $subjectCode);
        $classId = DB::table('classes')->insertGetId([
            'code' => 'CLS-' . $suffix,
            'name' => 'Class ' . $suffix,
            'course_year' => 2026,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $planId = DB::table('plans')->insertGetId([
            'training_batch_id' => null,
            'name' => 'Plan ' . $suffix,
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => null,
            'created_by' => null,
            'submitted_by' => null,
            'submitted_at' => null,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => '2026-07-01',
            'effective_to' => '2026-07-31',
            'approved_version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => 7,
            'year' => 2026,
            'created_by' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $roomId = DB::table('rooms')->insertGetId([
            'code' => 'RM-' . $suffix,
            'name' => 'Room ' . $suffix,
            'capacity' => 40,
            'room_type' => 'classroom',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $groupId = null;
        if ($useActiveMergeGroup) {
            $groupId = DB::table('schedule_slot_groups')->insertGetId([
                'monthly_schedule_id' => $monthlyScheduleId,
                'date' => $dateTime,
                'period_number' => $periodNumber,
                'subject_id' => $subjectId,
                'subject_lesson_id' => null,
                'teacher_id' => $teacherId,
                'room_id' => $roomId,
                'status' => 'active',
                'note' => 'Active merge group',
                'created_by' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $slotIds = [];
        for ($index = 0; $index < $slotCount; $index++) {
            $slotIds[] = DB::table('schedule_slots')->insertGetId([
                'monthly_schedule_id' => $monthlyScheduleId,
                'schedule_slot_group_id' => $groupId,
                'class_id' => $classId,
                'teacher_id' => $teacherId,
                'assignment_source' => 'internal',
                'teaching_support_request_item_id' => null,
                'subject_id' => $subjectId,
                'subject_lesson_id' => null,
                'room_id' => $roomId,
                'slot_type' => 'subject',
                'semester_event_id' => null,
                'event_type' => null,
                'date' => $dateTime,
                'day_of_week' => Carbon::parse($dateTime)->dayOfWeek,
                'period' => (string) $periodNumber,
                'period_number' => $periodNumber,
                'subject' => $subjectCode,
                'content' => null,
                'slot_status' => 'planned',
                'actual_content' => null,
                'note' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        return [
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'room_id' => $roomId,
            'slot_ids' => $slotIds,
            'group_id' => $groupId,
        ];
    }

    private function resetRelevantTables(): void
    {
        TestingDatabaseGuard::assertSafeBeforeBoot();
        TestingDatabaseGuard::assertSafeAfterBoot();

        $tables = [
            'teaching_support_request_items',
            'teaching_support_requests',
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

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        foreach ($tables as $table) {
            if ($isSqlite) {
                DB::table($table)->delete();
                continue;
            }

            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }
}
