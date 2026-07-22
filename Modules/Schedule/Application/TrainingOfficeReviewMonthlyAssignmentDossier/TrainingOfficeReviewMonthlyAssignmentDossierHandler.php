<?php

namespace Modules\Schedule\Application\TrainingOfficeReviewMonthlyAssignmentDossier;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class TrainingOfficeReviewMonthlyAssignmentDossierHandler
{
    /**
     * Handle Phong Dao tao leadership approval/rejection of a staff-submitted monthly assignment dossier.
     * Approving forwards the dossier straight to Ban Giam hieu (leadership_review); rejecting
     * returns it to the submitter for revision.
     */
    public function handle(TrainingOfficeReviewMonthlyAssignmentDossierRequest $request, int $id)
    {
        $validated = $request->validated();
        $isApproved = $validated['action'] === 'approve';

        try {
            DB::transaction(function () use ($request, $id, $isApproved, $validated): void {
                $dossier = MonthlyAssignmentDossier::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($dossier->status !== MonthlyAssignmentDossier::STATUS_SUBMITTED
                    || $dossier->current_step !== MonthlyAssignmentDossier::STEP_TRAINING_OFFICE_REVIEW) {
                    throw new TrainingOfficeReviewMonthlyAssignmentDossierStateException(
                        'Ho so phan cong thang nay dang khong o trang thai cho Phong Dao tao phe duyet. Vui long tai lai danh sach.'
                    );
                }

                $now = now();
                $actorId = $request->user()->id;

                $dossier->update($isApproved
                    ? [
                        'current_step' => MonthlyAssignmentDossier::STEP_LEADERSHIP_REVIEW,
                        'reviewed_by' => $actorId,
                        'reviewed_at' => $now,
                    ]
                    : [
                        'status' => MonthlyAssignmentDossier::STATUS_RETURNED,
                        'current_step' => MonthlyAssignmentDossier::STEP_DRAFT,
                        'reviewed_by' => $actorId,
                        'reviewed_at' => $now,
                    ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => MonthlyAssignmentDossier::class,
                    'entity_id' => $dossier->id,
                ]);
                $approvalRequest->current_step = $isApproved ? MonthlyAssignmentDossier::STEP_LEADERSHIP_REVIEW : MonthlyAssignmentDossier::STEP_TRAINING_OFFICE_REVIEW;
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

                app(InternalNotificationService::class)->notifyMonthlyAssignmentDossierTrainingOfficeReviewed(
                    $dossier->fresh(['submittedBy', 'createdBy']),
                    $request->user(),
                    $isApproved
                );
            });
        } catch (TrainingOfficeReviewMonthlyAssignmentDossierStateException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Khong the xu ly phe duyet ho so phan cong thang. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $id, $isApproved);
    }

    private function successResponse(TrainingOfficeReviewMonthlyAssignmentDossierRequest $request, int $dossierId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Ho so phan cong thang da duoc Phong Dao tao phe duyet va trinh len Ban Giam hieu.'
            : 'Ho so phan cong thang da bi Phong Dao tao tu choi.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'dossier_id' => $dossierId,
            ]);
        }

        return redirect()->route('monthly-assignment-dossiers.show', $dossierId)->with('success', $message);
    }

    private function errorResponse(TrainingOfficeReviewMonthlyAssignmentDossierRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}

final class TrainingOfficeReviewMonthlyAssignmentDossierStateException extends \RuntimeException
{
}
