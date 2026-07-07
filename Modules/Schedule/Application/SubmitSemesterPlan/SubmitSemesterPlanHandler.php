<?php

namespace Modules\Schedule\Application\SubmitSemesterPlan;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class SubmitSemesterPlanHandler
{
    /**
     * Handle submitting a semester plan to leadership.
     */
    public function handle(SubmitSemesterPlanRequest $request, int $id)
    {
        $plan = Plans::query()->findOrFail($id);

        if (! in_array($plan->status, ['draft', 'returned', 'rejected'], true)) {
            return $this->errorResponse($request, 'This semester plan cannot be submitted from its current status.', 422);
        }

        try {
            DB::transaction(function () use ($request, $plan): void {
                $now = now();
                $actorId = $request->user()->id;

                $plan->update([
                    'submitted_by' => $actorId,
                    'submitted_at' => $now,
                    'status' => 'submitted',
                    'current_step' => 'leadership_review',
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => Plans::class,
                    'entity_id' => $plan->id,
                ]);

                $approvalRequest->fill([
                    'submitted_by' => $actorId,
                    'current_step' => 'leadership_review',
                    'status' => 'pending',
                    'submitted_at' => $now,
                    'completed_at' => null,
                ]);
                $approvalRequest->save();

                ApprovalAction::query()->create([
                    'approval_request_id' => $approvalRequest->id,
                    'step_code' => 'submitted',
                    'action' => 'submit',
                    'acted_by' => $actorId,
                    'acted_at' => $now,
                    'comment' => $request->input('comment'),
                ]);

                app(InternalNotificationService::class)->notifySemesterPlanSubmitted($plan, $request->user());
            });
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Could not submit semester plan. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $plan->id);
    }

    private function successResponse(SubmitSemesterPlanRequest $request, int $planId)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Semester plan submitted to leadership successfully.',
                'plan_id' => $planId,
            ]);
        }

        return redirect()->route('schedule.index')
            ->with('success', 'Semester plan submitted to leadership successfully.');
    }

    private function errorResponse(SubmitSemesterPlanRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
