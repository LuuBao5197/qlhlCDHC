<?php

namespace Modules\Schedule\Application\LeadershipReviewSemesterPlan;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class LeadershipReviewSemesterPlanHandler
{
    /**
     * Handle Ban Giam hieu approval/rejection for a single semester plan.
     * Each plan is reviewed independently: no batching across plans.
     */
    public function handle(LeadershipReviewSemesterPlanRequest $request, int $id)
    {
        $validated = $request->validated();
        $isApproved = $validated['action'] === 'approve';

        try {
            DB::transaction(function () use ($request, $id, $isApproved, $validated): void {
                $plan = Plans::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($plan->status !== 'submitted' || $plan->current_step !== 'leadership_review') {
                    throw new LeadershipReviewSemesterPlanStateException(
                        'Ke hoach hoc ky nay dang khong o trang thai cho Ban Giam hieu phe duyet. Vui long tai lai danh sach.'
                    );
                }

                $now = now();
                $actorId = $request->user()->id;

                $plan->update([
                    'status' => $isApproved ? 'approved' : 'rejected',
                    'current_step' => $isApproved ? 'completed' : 'draft',
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => Plans::class,
                    'entity_id' => $plan->id,
                ]);
                $approvalRequest->current_step = 'leadership_review';
                $approvalRequest->status = $isApproved ? 'approved' : 'rejected';
                $approvalRequest->completed_at = $now;
                $approvalRequest->save();

                ApprovalAction::query()->create([
                    'approval_request_id' => $approvalRequest->id,
                    'step_code' => 'leadership_review',
                    'action' => $isApproved ? 'approve' : 'reject',
                    'acted_by' => $actorId,
                    'acted_at' => $now,
                    'comment' => $validated['reason'] ?? $validated['comment'] ?? null,
                ]);

                app(InternalNotificationService::class)->notifySemesterPlanReviewed(
                    $plan->fresh(['submittedBy', 'createdBy']),
                    $request->user(),
                    $isApproved
                );
            });
        } catch (LeadershipReviewSemesterPlanStateException $exception) {
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

    private function successResponse(LeadershipReviewSemesterPlanRequest $request, int $planId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Ke hoach hoc ky da duoc Ban Giam hieu phe duyet.'
            : 'Ke hoach hoc ky da bi Ban Giam hieu tu choi.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'plan_id' => $planId,
            ]);
        }

        return redirect()->route('schedule.index')->with('success', $message);
    }

    private function errorResponse(LeadershipReviewSemesterPlanRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}

final class LeadershipReviewSemesterPlanStateException extends \RuntimeException
{
}
