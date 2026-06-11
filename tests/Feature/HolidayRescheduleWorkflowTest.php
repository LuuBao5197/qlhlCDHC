<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Training\Models\ChangeRequest;
use Tests\TestCase;

class HolidayRescheduleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_reschedule_obeys_plan_template_weekdays_and_skips_to_next_valid_day(): void
    {
        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        [$monthlyScheduleId, $slotId] = $this->seedMonthlyScheduleWithTemplateBoundSlot(
            slotDateTime: '2026-09-14 00:00:00',
            periodNumber: 1,
            classId: 101,
            subjectId: 201,
            allowedDays: [2, 4, 6],
            templateStartDate: '2026-09-01',
            templateEndDate: '2026-12-31'
        );

        $response = $this
            ->actingAs($trainingOffice)
            ->from('/schedules')
            ->post('/change-requests/holiday-reschedule', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'holiday_start_date' => '2026-09-14',
                'holiday_end_date' => '2026-09-14',
                'target_date' => '2026-09-15',
                'reason' => 'Dieu chinh do nghi le',
                'apply_mode' => 'best_effort',
            ]);

        $response->assertRedirect('/schedules');

        $changeRequest = ChangeRequest::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->where('change_type', 'holiday_reschedule')
            ->latest('id')
            ->first();

        $this->assertNotNull(
            $changeRequest,
            (string) ($response->baseResponse->getSession()->get('error') ?? 'Khong tao duoc change request.')
        );

        if (! $changeRequest) {
            return;
        }

        $item = $changeRequest->changeRequestItems()->firstOrFail();
        $newPayload = is_array($item->new_payload) ? $item->new_payload : [];

        $this->assertSame($slotId, (int) $item->schedule_slot_id);
        $this->assertSame('2026-09-16', $newPayload['date'] ?? null);
        $this->assertSame(4, $newPayload['day_of_week'] ?? null);
    }

    public function test_holiday_reschedule_can_pick_weekend_when_template_allows_it(): void
    {
        config()->set('schedule.holiday_reschedule.allow_weekend_if_template_allows', true);

        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        [$monthlyScheduleId, $slotId] = $this->seedMonthlyScheduleWithTemplateBoundSlot(
            slotDateTime: '2026-05-01 00:00:00',
            periodNumber: 1,
            classId: 102,
            subjectId: 202,
            allowedDays: [7],
            templateStartDate: '2026-05-01',
            templateEndDate: '2026-05-31'
        );

        $response = $this
            ->actingAs($trainingOffice)
            ->from('/schedules')
            ->post('/change-requests/holiday-reschedule', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'holiday_start_date' => '2026-05-01',
                'holiday_end_date' => '2026-05-01',
                'target_date' => '2026-05-02',
                'reason' => 'Doi sang thu bay theo template',
                'apply_mode' => 'best_effort',
            ]);

        $response->assertRedirect('/schedules');

        $changeRequest = ChangeRequest::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->where('change_type', 'holiday_reschedule')
            ->latest('id')
            ->first();

        $this->assertNotNull(
            $changeRequest,
            (string) ($response->baseResponse->getSession()->get('error') ?? 'Khong tao duoc change request.')
        );

        if (! $changeRequest) {
            return;
        }

        $item = $changeRequest->changeRequestItems()->firstOrFail();
        $newPayload = is_array($item->new_payload) ? $item->new_payload : [];

        $this->assertSame($slotId, (int) $item->schedule_slot_id);
        $this->assertSame('2026-05-02', $newPayload['date'] ?? null);
        $this->assertSame(7, $newPayload['day_of_week'] ?? null);
    }

    public function test_training_office_can_create_holiday_reschedule_request(): void
    {
        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $monthlyScheduleId = $this->seedMonthlyScheduleWithOneSlot('2026-09-10 00:00:00', 1);

        $response = $this
            ->actingAs($trainingOffice)
            ->from('/schedules')
            ->post('/change-requests/holiday-reschedule', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'holiday_start_date' => '2026-09-10',
                'holiday_end_date' => '2026-09-10',
                'target_date' => '2026-09-11',
                'reason' => 'Dieu chinh do nghi le',
                'apply_mode' => 'best_effort',
            ]);

        $response->assertRedirect('/schedules');

        $changeRequest = ChangeRequest::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->where('change_type', 'holiday_reschedule')
            ->first();

        $this->assertNotNull($changeRequest);
        $this->assertSame('pending', $changeRequest->status);
        $this->assertSame($trainingOffice->id, $changeRequest->requested_by);
        $this->assertSame('best_effort', $changeRequest->apply_mode);

        $this->assertDatabaseHas('change_request_items', [
            'change_request_id' => $changeRequest->id,
        ]);
    }

    public function test_admin_can_approve_holiday_reschedule_created_by_training_office_and_slot_date_is_updated(): void
    {
        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $monthlyScheduleId = $this->seedMonthlyScheduleWithOneSlot('2026-11-03 00:00:00', 2);

        $this
            ->actingAs($trainingOffice)
            ->post('/change-requests/holiday-reschedule', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'holiday_start_date' => '2026-11-03',
                'holiday_end_date' => '2026-11-03',
                'target_date' => '2026-11-04',
                'reason' => 'Dieu chinh do nghi le',
                'apply_mode' => 'best_effort',
            ]);

        $changeRequest = ChangeRequest::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->where('change_type', 'holiday_reschedule')
            ->latest('id')
            ->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->from('/schedules')
            ->post('/change-requests/' . $changeRequest->id . '/review', [
                'action' => 'approve',
                'apply_changes' => 1,
                'apply_mode' => 'best_effort',
                'comment' => 'Duyet',
            ]);

        $response->assertRedirect('/schedules');

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'approved',
        ]);

        $item = $changeRequest->changeRequestItems()->firstOrFail();
        $newDate = is_array($item->new_payload) ? ($item->new_payload['date'] ?? null) : null;

        $this->assertNotNull($newDate);
        $this->assertDatabaseHas('schedule_slots', [
            'id' => $item->schedule_slot_id,
            'date' => $newDate . ' 00:00:00',
        ]);
    }

    public function test_admin_cannot_self_approve_holiday_reschedule_request(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $monthlyScheduleId = $this->seedMonthlyScheduleWithOneSlot('2026-12-01 00:00:00', 3);

        $this
            ->actingAs($admin)
            ->post('/change-requests/holiday-reschedule', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'holiday_start_date' => '2026-12-01',
                'holiday_end_date' => '2026-12-01',
                'target_date' => '2026-12-02',
                'reason' => 'Dieu chinh do nghi le',
                'apply_mode' => 'best_effort',
            ]);

        $changeRequest = ChangeRequest::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->where('change_type', 'holiday_reschedule')
            ->latest('id')
            ->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->from('/schedules')
            ->post('/change-requests/' . $changeRequest->id . '/review', [
                'action' => 'approve',
                'apply_changes' => 1,
                'apply_mode' => 'best_effort',
            ]);

        $response->assertRedirect('/schedules');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'pending',
        ]);
    }

    public function test_holiday_review_apply_excludes_batch_slots_to_avoid_false_collision(): void
    {
        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan test batch apply consistency',
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
            'month' => 7,
            'year' => 2026,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = DB::table('classes')->insertGetId([
            'code' => 'CLS-BATCH-01',
            'name' => 'Class batch test',
            'course_year' => 2026,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotAId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $classId,
            'teacher_id' => null,
            'subject_id' => null,
            'subject_lesson_id' => null,
            'room_id' => null,
            'date' => '2026-05-01 00:00:00',
            'day_of_week' => 5,
            'period' => 'Sang',
            'period_number' => 1,
            'subject' => 'Mon hoc A',
            'content' => 'Noi dung',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotBId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $classId,
            'teacher_id' => null,
            'subject_id' => null,
            'subject_lesson_id' => null,
            'room_id' => null,
            'date' => '2026-05-02 00:00:00',
            'day_of_week' => 6,
            'period' => 'Sang',
            'period_number' => 1,
            'subject' => 'Mon hoc B',
            'content' => 'Noi dung',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $changeRequestId = DB::table('change_requests')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'schedule_slot_id' => $slotAId,
            'requested_by' => $trainingOffice->id,
            'reason' => 'Batch move to avoid holiday',
            'old_payload' => json_encode(['date' => '2026-05-01', 'period_number' => 1]),
            'new_payload' => json_encode(['date' => '2026-05-02', 'day_of_week' => 7, 'period_number' => 1]),
            'status' => 'pending',
            'change_type' => 'holiday_reschedule',
            'apply_mode' => 'best_effort',
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
                'schedule_slot_id' => $slotAId,
                'old_payload' => json_encode(['date' => '2026-05-01', 'period_number' => 1]),
                'new_payload' => json_encode(['date' => '2026-05-02', 'day_of_week' => 7, 'period_number' => 1]),
                'apply_status' => null,
                'apply_error' => null,
                'applied_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'change_request_id' => $changeRequestId,
                'schedule_slot_id' => $slotBId,
                'old_payload' => json_encode(['date' => '2026-05-02', 'period_number' => 1]),
                'new_payload' => json_encode(['date' => '2026-05-03', 'day_of_week' => 8, 'period_number' => 1]),
                'apply_status' => null,
                'apply_error' => null,
                'applied_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->from('/schedules')
            ->post('/change-requests/' . $changeRequestId . '/review', [
                'action' => 'approve',
                'apply_changes' => 1,
                'apply_mode' => 'best_effort',
                'comment' => 'Duyet batch holiday move',
            ]);

        $response->assertRedirect('/schedules');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequestId,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('change_request_items', [
            'change_request_id' => $changeRequestId,
            'schedule_slot_id' => $slotAId,
            'apply_status' => 'applied',
        ]);

        $this->assertDatabaseHas('change_request_items', [
            'change_request_id' => $changeRequestId,
            'schedule_slot_id' => $slotBId,
            'apply_status' => 'applied',
        ]);

        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotAId,
            'date' => '2026-05-02 00:00:00',
            'period_number' => 1,
        ]);

        $this->assertDatabaseHas('schedule_slots', [
            'id' => $slotBId,
            'date' => '2026-05-03 00:00:00',
            'period_number' => 1,
        ]);
    }

    public function test_holiday_chain_shift_is_bounded_in_month_and_overflow_slots_are_dropped(): void
    {
        $trainingOffice = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan test month bounded chain shift',
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
            'month' => 7,
            'year' => 2026,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = DB::table('classes')->insertGetId([
            'code' => 'CLS-CHAIN-MONTH-01',
            'name' => 'Class chain month test',
            'course_year' => 2026,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstSlotId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $classId,
            'teacher_id' => null,
            'subject_id' => null,
            'subject_lesson_id' => null,
            'room_id' => null,
            'date' => '2026-07-30 00:00:00',
            'day_of_week' => 7,
            'period' => 'Sang',
            'period_number' => 1,
            'subject' => 'Mon hoc A',
            'content' => 'Noi dung',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondSlotId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $classId,
            'teacher_id' => null,
            'subject_id' => null,
            'subject_lesson_id' => null,
            'room_id' => null,
            'date' => '2026-07-31 00:00:00',
            'day_of_week' => 8,
            'period' => 'Sang',
            'period_number' => 1,
            'subject' => 'Mon hoc B',
            'content' => 'Noi dung',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($trainingOffice)
            ->from('/schedules')
            ->post('/change-requests/holiday-reschedule', [
                'monthly_schedule_id' => $monthlyScheduleId,
                'holiday_start_date' => '2026-07-30',
                'holiday_end_date' => '2026-07-30',
                'target_date' => '2026-07-31',
                'reason' => 'Chain shift trong thang voi tran thang',
                'apply_mode' => 'best_effort',
            ]);

        $response->assertRedirect('/schedules');

        $changeRequest = ChangeRequest::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->where('change_type', 'holiday_reschedule')
            ->latest('id')
            ->first();

        $this->assertNotNull(
            $changeRequest,
            (string) ($response->baseResponse->getSession()->get('error') ?? 'Khong tao duoc change request.')
        );

        if (! $changeRequest) {
            return;
        }

        $items = $changeRequest->changeRequestItems()->get();
        $this->assertCount(1, $items);
        $this->assertSame($firstSlotId, (int) $items->first()->schedule_slot_id);

        $summary = is_array($changeRequest->apply_summary)
            ? $changeRequest->apply_summary
            : (json_decode((string) $changeRequest->apply_summary, true) ?: []);

        $this->assertSame(2, (int) ($summary['attempted'] ?? -1));
        $this->assertSame(1, (int) ($summary['schedulable'] ?? -1));
        $this->assertSame(1, (int) ($summary['dropped'] ?? -1));
        $this->assertSame(1, (int) ($summary['unschedulable'] ?? -1));

        $errors = is_array($summary['errors'] ?? null) ? $summary['errors'] : [];
        $this->assertNotEmpty($errors);
        $this->assertSame($secondSlotId, (int) ($errors[0]['slot_id'] ?? 0));
    }

    private function seedMonthlyScheduleWithOneSlot(string $slotDateTime, int $periodNumber): int
    {
        $slotDate = Carbon::parse($slotDateTime);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan test holiday',
            'semester' => 1,
            'year' => (int) $slotDate->year,
            'file_path' => null,
            'description' => 'Plan test',
            'status' => 'approved',
            'current_step' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => (int) $slotDate->month,
            'year' => (int) $slotDate->year,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('schedule_slots')->insert([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => null,
            'teacher_id' => null,
            'subject_id' => null,
            'subject_lesson_id' => null,
            'room_id' => null,
            'date' => $slotDateTime,
            'day_of_week' => 2,
            'period' => 'Sang',
            'period_number' => $periodNumber,
            'subject' => 'Mon hoc test',
            'content' => 'Noi dung',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $monthlyScheduleId;
    }

    /**
     * @param array<int> $allowedDays
     * @return array{0: int, 1: int}
     */
    private function seedMonthlyScheduleWithTemplateBoundSlot(
        string $slotDateTime,
        int $periodNumber,
        int $classId,
        int $subjectId,
        array $allowedDays,
        string $templateStartDate,
        string $templateEndDate
    ): array {
        $slotDate = Carbon::parse($slotDateTime);

        $planId = DB::table('plans')->insertGetId([
            'name' => 'Plan test template holiday',
            'semester' => 1,
            'year' => (int) $slotDate->year,
            'file_path' => null,
            'description' => 'Plan test template',
            'status' => 'approved',
            'current_step' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $monthlyScheduleId = DB::table('monthly_schedules')->insertGetId([
            'plan_id' => $planId,
            'month' => (int) $slotDate->month,
            'year' => (int) $slotDate->year,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classRecordId = DB::table('classes')->insertGetId([
            'code' => 'CLS-' . $classId,
            'name' => 'Class ' . $classId,
            'course_year' => 2026,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectRecordId = DB::table('subjects')->insertGetId([
            'code' => 'SUB-' . $subjectId,
            'name' => 'Subject ' . $subjectId,
            'total_periods' => 30,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('plan_templates')->insert([
            'plan_id' => $planId,
            'class_id' => $classRecordId,
            'subject_id' => $subjectRecordId,
            'day_of_week' => $allowedDays[0],
            'days_of_week' => json_encode($allowedDays),
            'session' => 'Sang',
            'period_range' => '1-5',
            'description' => 'Template test holiday',
            'start_date' => $templateStartDate,
            'end_date' => $templateEndDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotId = DB::table('schedule_slots')->insertGetId([
            'monthly_schedule_id' => $monthlyScheduleId,
            'class_id' => $classRecordId,
            'teacher_id' => null,
            'subject_id' => $subjectRecordId,
            'subject_lesson_id' => null,
            'room_id' => null,
            'date' => $slotDateTime,
            'day_of_week' => 2,
            'period' => 'Sang',
            'period_number' => $periodNumber,
            'subject' => 'Mon hoc test',
            'content' => 'Noi dung',
            'slot_status' => 'planned',
            'actual_content' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$monthlyScheduleId, $slotId];
    }
}
