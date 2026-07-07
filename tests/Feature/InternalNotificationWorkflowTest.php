<?php

namespace Tests\Feature;

use App\Enums\InternalNotificationType;
use App\Models\User;
use App\Notifications\InternalNotification;
use App\Services\InternalNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\Department;
use Tests\TestCase;

class InternalNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_semester_plan_submission_notifies_admin_and_leadership_only(): void
    {
        Carbon::setTestNow('2026-07-05 09:00:00');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);
        $leadership = User::factory()->create([
            'role' => User::ROLE_LEADERSHIP,
            'status' => User::STATUS_APPROVED,
        ]);
        $department = Department::factory()->create([
            'status' => 'active',
        ]);
        $staff = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
            'department_id' => $department->id,
        ]);
        $actor = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
        ]);

        $planId = DB::table('plans')->insertGetId([
            'training_batch_id' => null,
            'name' => 'Semester plan',
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => null,
            'created_by' => $actor->id,
            'submitted_by' => null,
            'submitted_at' => null,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => '2026-07-01',
            'effective_to' => '2026-12-31',
            'approved_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($actor)->postJson(route('schedule.submit', $planId), [
            'comment' => 'Submit for review',
        ])->assertSuccessful();

        $this->assertSame(1, $admin->fresh()->notifications()->count());
        $this->assertSame(1, $leadership->fresh()->notifications()->count());
        $this->assertSame(0, $staff->fresh()->notifications()->count());

        $adminNotification = $admin->fresh()->notifications()->first();
        $this->assertNotNull($adminNotification);
        $this->assertSame(InternalNotificationType::SEMESTER_PLAN_SUBMITTED->value, data_get($adminNotification->data, 'type'));
        $this->assertSame(route('schedule.show', $planId), data_get($adminNotification->data, 'url'));
    }

    public function test_notification_dropdown_and_mark_all_read_workflow(): void
    {
        Carbon::setTestNow('2026-07-05 10:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        app(InternalNotificationService::class)->notifyUsers([$user], new InternalNotification(
            InternalNotificationType::ASSIGNMENT_BATCH_SUBMITTED,
            'Batch phan cong da duoc gui',
            'Batch phan cong cua khoa A dang cho PDT duyet.',
            route('department-monthly-assignment-batches.index'),
            'batch-test-1',
            [
                'batch_id' => 1,
            ]
        ));

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('Batch phan cong da duoc gui', false)
            ->assertSee('count bg-danger', false);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Thong bao noi bo', false)
            ->assertSee('Batch phan cong da duoc gui', false);

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_notification_show_marks_as_read_and_redirects_to_target_url(): void
    {
        Carbon::setTestNow('2026-07-05 11:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        app(InternalNotificationService::class)->notifyUsers([$user], new InternalNotification(
            InternalNotificationType::TEACHING_SUPPORT_REQUEST_SUBMITTED,
            'Co de nghi ho tro moi',
            'De nghi ho tro #99 vua duoc gui len PDT.',
            route('teaching-support-requests.index'),
            'support-request-test-1',
            [
                'request_id' => 99,
            ]
        ));

        $notification = $user->fresh()->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('teaching-support-requests.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_duplicate_notification_is_not_created_twice_for_same_dedupe_key(): void
    {
        Carbon::setTestNow('2026-07-05 12:00:00');

        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $service = app(InternalNotificationService::class);
        $notification = new InternalNotification(
            InternalNotificationType::USER_REGISTRATION_PENDING,
            'Co tai khoan dang ky moi',
            'Tai khoan A vua gui yeu cau dang ky va can duoc xem xet.',
            route('admin.index'),
            'duplicate-test-1',
            [
                'user_id' => 123,
            ]
        );

        $service->notifyUsers([$user], $notification);
        $service->notifyUsers([$user], $notification);

        $this->assertSame(1, $user->fresh()->notifications()->count());
    }
}
