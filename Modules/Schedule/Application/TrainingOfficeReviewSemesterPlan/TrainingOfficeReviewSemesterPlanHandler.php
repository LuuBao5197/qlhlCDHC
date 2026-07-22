<?php

namespace Modules\Schedule\Application\TrainingOfficeReviewSemesterPlan;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class TrainingOfficeReviewSemesterPlanHandler
{
    /**
     * Handle Phong Dao tao leadership approval/rejection of a staff-submitted semester plan.
     * Approving forwards the plan straight to Ban Giam hieu (leadership_review); rejecting
     * returns it to the submitter for revision. Each plan is reviewed independently.
     */
    public function handle(TrainingOfficeReviewSemesterPlanRequest $request, int $id)
    {
        $validated = $request->validated();
        $isApproved = $validated['action'] === 'approve';

        try {
            DB::transaction(function () use ($request, $id, $isApproved, $validated): void {
                $plan = Plans::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($plan->status !== 'submitted' || $plan->current_step !== 'training_office_review') {
                    throw new TrainingOfficeReviewSemesterPlanStateException(
                        'Ke hoach hoc ky nay dang khong o trang thai cho Phong Dao tao phe duyet. Vui long tai lai danh sach.'
                    );
                }

                $now = now();
                $actorId = $request->user()->id;

                $plan->update($isApproved
                    ? ['current_step' => 'leadership_review']
                    : ['status' => 'returned', 'current_step' => 'draft']);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => Plans::class,
                    'entity_id' => $plan->id,
                ]);
                $approvalRequest->current_step = $isApproved ? 'leadership_review' : 'training_office_review';
                $approvalRequest->status = $isApproved ? 'pending' : 'returned';
                $approvalRequest->completed_at = $isApproved ? null : $now;
                $approvalRequest->save();

                ApprovalAction::query()->create([
                    'approval_request_id' => $approvalRequest->id,
                    'step_code' => 'training_office_review',
                    'action' => $isApproved ? 'approve' : 'reject',
                    'acted_by' => $actorId,
                    'acted_at' => $now,
                    'comment' => $validated['reason'] ?? $validated['comment'] ?? null,
                ]);

                app(InternalNotificationService::class)->notifySemesterPlanTrainingOfficeReviewed(
                    $plan->fresh(['submittedBy', 'createdBy']),
                    $request->user(),
                    $isApproved
                );
            });
        } catch (TrainingOfficeReviewSemesterPlanStateException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Khong the xu ly phe duyet ke hoach hoc ky. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $id, $isApproved);
    }

    private function successResponse(TrainingOfficeReviewSemesterPlanRequest $request, int $planId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Ke hoach hoc ky da duoc Phong Dao tao phe duyet va trinh len Ban Giam hieu.'
            : 'Ke hoach hoc ky da bi Phong Dao tao tu choi.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'plan_id' => $planId,
            ]);
        }

        return redirect()->route('schedule.index')->with('success', $message);
    }

    private function errorResponse(TrainingOfficeReviewSemesterPlanRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}

final class TrainingOfficeReviewSemesterPlanStateException extends \RuntimeException
{
}
