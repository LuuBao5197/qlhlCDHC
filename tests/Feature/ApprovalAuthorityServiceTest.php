<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\User;
use App\Services\ApprovalAuthorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Training\Models\Department;
use Tests\TestCase;

class ApprovalAuthorityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_office_approval_requires_head_or_deputy_head_position(): void
    {
        $service = app(ApprovalAuthorityService::class);

        $head = User::factory()->make([
            'role' => User::ROLE_TRAINING_OFFICE,
            'position' => Position::TRAINING_HEAD,
        ]);
        $deputyHead = User::factory()->make([
            'role' => User::ROLE_TRAINING_OFFICE,
            'position' => Position::TRAINING_DEPUTY_HEAD,
        ]);
        $staff = User::factory()->make([
            'role' => User::ROLE_TRAINING_OFFICE,
            'position' => Position::TRAINING_STAFF,
        ]);
        $noPosition = User::factory()->make([
            'role' => User::ROLE_TRAINING_OFFICE,
            'position' => null,
        ]);
        $wrongRole = User::factory()->make([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'position' => Position::DEPARTMENT_HEAD,
        ]);
        $admin = User::factory()->make([
            'role' => User::ROLE_ADMIN,
            'position' => null,
        ]);

        $this->assertTrue($service->canApproveAsTrainingOffice($head));
        $this->assertTrue($service->canApproveAsTrainingOffice($deputyHead));
        $this->assertFalse($service->canApproveAsTrainingOffice($staff));
        $this->assertFalse($service->canApproveAsTrainingOffice($noPosition));
        $this->assertFalse($service->canApproveAsTrainingOffice($wrongRole));
        $this->assertTrue($service->canApproveAsTrainingOffice($admin));
        $this->assertFalse($service->canApproveAsTrainingOffice(null));
    }

    public function test_leadership_approval_requires_vice_principal_or_principal_position(): void
    {
        $service = app(ApprovalAuthorityService::class);

        $principal = User::factory()->make([
            'role' => User::ROLE_LEADERSHIP,
            'position' => Position::PRINCIPAL,
        ]);
        $vicePrincipal = User::factory()->make([
            'role' => User::ROLE_LEADERSHIP,
            'position' => Position::VICE_PRINCIPAL,
        ]);
        $noPosition = User::factory()->make([
            'role' => User::ROLE_LEADERSHIP,
            'position' => null,
        ]);
        $trainingHead = User::factory()->make([
            'role' => User::ROLE_TRAINING_OFFICE,
            'position' => Position::TRAINING_HEAD,
        ]);

        $this->assertTrue($service->canApproveAsLeadership($principal));
        $this->assertTrue($service->canApproveAsLeadership($vicePrincipal));
        $this->assertFalse($service->canApproveAsLeadership($noPosition));
        $this->assertFalse($service->canApproveAsLeadership($trainingHead));
    }

    /**
     * Batch da duoc Lanh dao Khoa duyet (current_step=training_office_review) chi duoc PDT
     * Truong/Pho truong phe duyet; Training Staff khong du tham quyen.
     */
    public function test_training_staff_cannot_approve_monthly_assignment_batch_but_training_head_can(): void
    {
        $department = Department::factory()->create([
            'code' => 'KHOA-POS',
            'name' => 'Khoa Position Test',
            'status' => 'active',
        ]);

        $batch = DepartmentMonthlyAssignmentBatch::query()->create([
            'department_id' => $department->id,
            'month' => 8,
            'year' => 2026,
            'status' => 'submitted',
            'current_step' => 'training_office_review',
            'version' => 1,
        ]);

        $trainingStaff = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_STAFF,
        ]);

        $this->actingAs($trainingStaff)
            ->post(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertForbidden();

        $this->assertDatabaseHas('department_monthly_assignment_batches', [
            'id' => $batch->id,
            'status' => 'submitted',
            'current_step' => 'training_office_review',
        ]);

        $trainingHead = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_HEAD,
        ]);

        $this->actingAs($trainingHead)
            ->post(route('department-monthly-assignment-batches.training-office-review', $batch->id), ['action' => 'approve'])
            ->assertRedirect();

        $this->assertDatabaseHas('department_monthly_assignment_batches', [
            'id' => $batch->id,
            'status' => 'approved',
            'current_step' => 'completed',
        ]);
    }

    /**
     * Duyet "approve" batch (bước Lãnh đạo Khoa) giờ chỉ dành cho đúng Khoa, PDT không còn được gọi truc tiep.
     */
    public function test_training_head_cannot_use_department_leadership_approve_route(): void
    {
        $department = Department::factory()->create([
            'code' => 'KHOA-POS-2',
            'name' => 'Khoa Position Test 2',
            'status' => 'active',
        ]);

        $batch = DepartmentMonthlyAssignmentBatch::query()->create([
            'department_id' => $department->id,
            'month' => 9,
            'year' => 2026,
            'status' => 'submitted',
            'current_step' => 'department_review',
            'version' => 1,
        ]);

        $trainingHead = User::factory()->create([
            'role' => User::ROLE_TRAINING_OFFICE,
            'status' => User::STATUS_APPROVED,
            'position' => Position::TRAINING_HEAD,
        ]);

        $this->actingAs($trainingHead)
            ->post(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertForbidden();

        $departmentHead = User::factory()->create([
            'role' => User::ROLE_DEPARTMENT_STAFF,
            'status' => User::STATUS_APPROVED,
            'position' => Position::DEPARTMENT_HEAD,
            'department_id' => $department->id,
        ]);

        $this->actingAs($departmentHead)
            ->post(route('department-monthly-assignment-batches.approve', $batch->id))
            ->assertRedirect();

        $this->assertDatabaseHas('department_monthly_assignment_batches', [
            'id' => $batch->id,
            'status' => 'submitted',
            'current_step' => 'training_office_review',
        ]);
    }
}
