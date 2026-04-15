<?php

namespace Modules\Schedule\Application\ReviewMonthlySchedule;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class ReviewMonthlyScheduleHandler
{
    /**
     * Handle review (approve/reject) for monthly teaching schedules.
     */
    public function handle(ReviewMonthlyScheduleRequest $request, int $id)
    {
        $monthlySchedule = MonthlySchedule::query()->findOrFail($id);
        $validated = $request->validated();
        $isApproved = $validated['action'] === 'approve';

        if (! in_array($monthlySchedule->status, ['pending', 'processing', 'submitted', 'returned'], true)) {
            return $this->errorResponse($request, 'This monthly schedule is not in a reviewable state.', 422);
        }

        try {
            DB::transaction(function () use ($request, $monthlySchedule, $validated, $isApproved): void {
                $now = now();
                $actorId = $request->user()->id;

                $monthlySchedule->update([
                    'status' => $isApproved ? 'approved' : 'rejected',
                    'approved_at' => $isApproved ? $now : null,
                    'approved_by' => $isApproved ? $actorId : null,
                    'rejection_reason' => $isApproved ? null : ($validated['reason'] ?? null),
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => MonthlySchedule::class,
                    'entity_id' => $monthlySchedule->id,
                ]);

                if (! $approvalRequest->exists) {
                    $approvalRequest->submitted_by = $monthlySchedule->created_by;
                    $approvalRequest->submitted_at = $monthlySchedule->submitted_at ?? $now;
                }

                $approvalRequest->current_step = 'training_review';
                $approvalRequest->status = $isApproved ? 'approved' : 'rejected';
                $approvalRequest->completed_at = $now;
                $approvalRequest->save();

                ApprovalAction::query()->create([
                    'approval_request_id' => $approvalRequest->id,
                    'step_code' => 'training_review',
                    'action' => $isApproved ? 'approve' : 'reject',
                    'acted_by' => $actorId,
                    'acted_at' => $now,
                    'comment' => $this->buildComment($validated),
                ]);
            });
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Could not review monthly schedule. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $monthlySchedule->id, $isApproved);
    }

    private function buildComment(array $validated): ?string
    {
        $parts = array_filter([
            $validated['reason'] ?? null,
            $validated['comment'] ?? null,
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function successResponse(ReviewMonthlyScheduleRequest $request, int $monthlyScheduleId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Monthly schedule approved successfully.'
            : 'Monthly schedule rejected successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'monthly_schedule_id' => $monthlyScheduleId,
            ]);
        }

        return redirect()->route('schedule.index')->with('success', $message);
    }

    private function errorResponse(ReviewMonthlyScheduleRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
