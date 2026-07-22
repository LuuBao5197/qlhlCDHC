<?php

namespace Modules\Schedule\Application\LeadershipReviewMonthlyAssignmentDossier;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class LeadershipReviewMonthlyAssignmentDossierHandler
{
    /**
     * Handle Ban Giam hieu approval/rejection for a monthly assignment dossier.
     */
    public function handle(LeadershipReviewMonthlyAssignmentDossierRequest $request, int $id)
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
                    || $dossier->current_step !== MonthlyAssignmentDossier::STEP_LEADERSHIP_REVIEW) {
                    throw new LeadershipReviewMonthlyAssignmentDossierStateException(
                        'Ho so phan cong thang nay dang khong o trang thai cho Ban Giam hieu phe duyet. Vui long tai lai danh sach.'
                    );
                }

                $now = now();
                $actorId = $request->user()->id;

                $dossier->update([
                    'status' => $isApproved ? MonthlyAssignmentDossier::STATUS_APPROVED : MonthlyAssignmentDossier::STATUS_REJECTED,
                    'current_step' => $isApproved ? MonthlyAssignmentDossier::STEP_COMPLETED : MonthlyAssignmentDossier::STEP_DRAFT,
                    'completed_at' => $now,
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => MonthlyAssignmentDossier::class,
                    'entity_id' => $dossier->id,
                ]);
                $approvalRequest->current_step = MonthlyAssignmentDossier::STEP_LEADERSHIP_REVIEW;
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

                app(InternalNotificationService::class)->notifyMonthlyAssignmentDossierReviewed(
                    $dossier->fresh(['submittedBy', 'createdBy']),
                    $request->user(),
                    $isApproved
                );
            });
        } catch (LeadershipReviewMonthlyAssignmentDossierStateException $exception) {
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

    private function successResponse(LeadershipReviewMonthlyAssignmentDossierRequest $request, int $dossierId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Ho so phan cong thang da duoc Ban Giam hieu phe duyet.'
            : 'Ho so phan cong thang da bi Ban Giam hieu tu choi.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'dossier_id' => $dossierId,
            ]);
        }

        return redirect()->route('monthly-assignment-dossiers.show', $dossierId)->with('success', $message);
    }

    private function errorResponse(LeadershipReviewMonthlyAssignmentDossierRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}

final class LeadershipReviewMonthlyAssignmentDossierStateException extends \RuntimeException
{
}
