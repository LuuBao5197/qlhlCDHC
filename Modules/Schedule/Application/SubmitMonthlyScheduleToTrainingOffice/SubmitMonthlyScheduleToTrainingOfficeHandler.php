<?php

namespace Modules\Schedule\Application\SubmitMonthlyScheduleToTrainingOffice;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class SubmitMonthlyScheduleToTrainingOfficeHandler
{
    /**
     * Submit monthly schedule to training office for review.
     */
    public function handle(SubmitMonthlyScheduleToTrainingOfficeRequest $request, int $id)
    {
        $monthlySchedule = MonthlySchedule::query()->with('scheduleSlots')->findOrFail($id);

        if (!in_array($monthlySchedule->status, ['draft', 'returned', 'rejected', 'pending'], true)) {
            return $this->errorResponse(
                $request,
                'Lich thang hien khong the trinh duyet o trang thai nay.',
                422
            );
        }

        $hasAnyAssignedSubject = $monthlySchedule->scheduleSlots()->where(function ($query): void {
            $query->whereNotNull('subject_id')->orWhereNotNull('subject');
        })->exists();

        if (!$hasAnyAssignedSubject) {
            return $this->errorResponse(
                $request,
                'Can phan cong it nhat 1 tiet hoc truoc khi gui duyet.',
                422
            );
        }

        try {
            DB::transaction(function () use ($request, $monthlySchedule): void {
                $now = now();
                $actorId = $request->user()->id;

                $monthlySchedule->update([
                    'status' => 'pending',
                    'submitted_at' => $now,
                    'rejection_reason' => null,
                    'approved_at' => null,
                    'approved_by' => null,
                    'created_by' => $monthlySchedule->created_by ?? $actorId,
                    'department_id' => $monthlySchedule->department_id ?? $request->user()->department_id,
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => MonthlySchedule::class,
                    'entity_id' => $monthlySchedule->id,
                ]);

                $approvalRequest->fill([
                    'submitted_by' => $actorId,
                    'current_step' => 'training_review',
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
            });
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Khong the gui duyet lich thang: ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $id);
    }

    private function successResponse(SubmitMonthlyScheduleToTrainingOfficeRequest $request, int $id)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Da gui lich thang len Phong dao tao de duyet.',
                'monthly_schedule_id' => $id,
            ]);
        }

        return redirect()->route('monthly-schedule.assignment', $id)
            ->with('success', 'Da gui lich thang len Phong dao tao de duyet.');
    }

    private function errorResponse(SubmitMonthlyScheduleToTrainingOfficeRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
