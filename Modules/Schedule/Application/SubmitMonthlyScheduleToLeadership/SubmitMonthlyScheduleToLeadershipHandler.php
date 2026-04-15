<?php

namespace Modules\Schedule\Application\SubmitMonthlyScheduleToLeadership;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class SubmitMonthlyScheduleToLeadershipHandler
{
    /**
     * Handle forwarding approved monthly schedule to leadership.
     */
    public function handle(SubmitMonthlyScheduleToLeadershipRequest $request, int $id)
    {
        $monthlySchedule = MonthlySchedule::query()->findOrFail($id);

        if ($monthlySchedule->status !== 'approved') {
            return $this->errorResponse(
                $request,
                'Only approved monthly schedules can be submitted to leadership.',
                422
            );
        }

        try {
            DB::transaction(function () use ($request, $monthlySchedule): void {
                $now = now();
                $actorId = $request->user()->id;

                $monthlySchedule->update([
                    'status' => 'submitted',
                    'submitted_at' => $now,
                    'rejection_reason' => null,
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => MonthlySchedule::class,
                    'entity_id' => $monthlySchedule->id,
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
                    'step_code' => 'leadership_review',
                    'action' => 'submit',
                    'acted_by' => $actorId,
                    'acted_at' => $now,
                    'comment' => $request->input('comment'),
                ]);
            });
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Could not submit monthly schedule to leadership. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $monthlySchedule->id);
    }

    private function successResponse(SubmitMonthlyScheduleToLeadershipRequest $request, int $monthlyScheduleId)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Monthly schedule submitted to leadership successfully.',
                'monthly_schedule_id' => $monthlyScheduleId,
            ]);
        }

        return redirect()->route('schedule.index')
            ->with('success', 'Monthly schedule submitted to leadership successfully.');
    }

    private function errorResponse(SubmitMonthlyScheduleToLeadershipRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
