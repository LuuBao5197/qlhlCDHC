<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Schedule\Models\Plans;
use Tests\TestCase;

class SemesterPlanApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(User $creator): Plans
    {
        return Plans::create([
            'training_batch_id' => null,
            'name' => 'Semester plan',
            'semester' => 1,
            'year' => 2026,
            'file_path' => null,
            'description' => null,
            'created_by' => $creator->id,
            'submitted_by' => null,
            'submitted_at' => null,
            'status' => 'draft',
            'current_step' => 'draft',
            'effective_from' => '2026-07-01',
            'effective_to' => '2026-12-31',
            'approved_version' => 1,
        ]);
    }

    public function test_training_office_staff_can_submit_but_cannot_approve_own_submission(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_STAFF,
        ]);
        $plan = $this->makePlan($staff);

        $this->actingAs($staff)
            ->postJson(route('schedule.submit', $plan->id), ['comment' => 'Please review'])
            ->assertSuccessful();

        $plan->refresh();
        $this->assertSame('submitted', $plan->status);
        $this->assertSame('training_office_review', $plan->current_step);

        $this->actingAs($staff)
            ->postJson(route('schedule.training-office-review', $plan->id), ['action' => 'approve'])
            ->assertForbidden();
    }

    public function test_training_office_head_approves_staff_submission_and_forwards_to_leadership(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_STAFF,
        ]);
        $head = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_HEAD,
        ]);
        $principal = User::factory()->create([
            'role' => User::ROLE_LEADERSHIP,
            'status' => User::STATUS_APPROVED,
            'position' => Position::PRINCIPAL,
        ]);

        $plan = $this->makePlan($staff);

        $this->actingAs($staff)
            ->postJson(route('schedule.submit', $plan->id), ['comment' => 'Please review'])
            ->assertSuccessful();

        // Leadership (BGH) cannot approve before Training Office has reviewed it.
        $this->actingAs($principal)
            ->postJson(route('schedule.leadership-review', $plan->id), ['action' => 'approve'])
            ->assertStatus(422);

        $this->actingAs($head)
            ->postJson(route('schedule.training-office-review', $plan->id), ['action' => 'approve'])
            ->assertSuccessful();

        $plan->refresh();
        $this->assertSame('submitted', $plan->status);
        $this->assertSame('leadership_review', $plan->current_step);

        $this->actingAs($principal)
            ->postJson(route('schedule.leadership-review', $plan->id), ['action' => 'approve'])
            ->assertSuccessful();

        $plan->refresh();
        $this->assertSame('approved', $plan->status);
        $this->assertSame('completed', $plan->current_step);
    }

    public function test_training_office_head_rejection_returns_plan_to_submitter(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_STAFF,
        ]);
        $head = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_HEAD,
        ]);

        $plan = $this->makePlan($staff);

        $this->actingAs($staff)
            ->postJson(route('schedule.submit', $plan->id), ['comment' => 'Please review'])
            ->assertSuccessful();

        $this->actingAs($head)
            ->postJson(route('schedule.training-office-review', $plan->id), [
                'action' => 'reject',
                'reason' => 'Missing data',
            ])
            ->assertSuccessful();

        $plan->refresh();
        $this->assertSame('returned', $plan->status);
        $this->assertSame('draft', $plan->current_step);

        // Staff can resubmit after a Training Office rejection.
        $this->actingAs($staff)
            ->postJson(route('schedule.submit', $plan->id), ['comment' => 'Fixed'])
            ->assertSuccessful();

        $plan->refresh();
        $this->assertSame('training_office_review', $plan->current_step);
    }
}
