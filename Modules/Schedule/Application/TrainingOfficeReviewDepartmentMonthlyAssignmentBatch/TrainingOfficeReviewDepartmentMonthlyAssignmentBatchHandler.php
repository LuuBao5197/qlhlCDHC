<?php

namespace Modules\Schedule\Application\TrainingOfficeReviewDepartmentMonthlyAssignmentBatch;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Throwable;

/**
 * Buoc "PDT duyet" mot batch phan cong cua Khoa, sau khi Lanh dao Khoa da duyet.
 * Duyet: batch hoan tat (status=approved), san sang de PDT tong hop vao MonthlyAssignmentDossier.
 * Tu choi: batch tra ve draft, Khoa phai sua va duoc Lanh dao Khoa duyet lai tu dau.
 */
class TrainingOfficeReviewDepartmentMonthlyAssignmentBatchHandler
{
    public function handle(TrainingOfficeReviewDepartmentMonthlyAssignmentBatchRequest $request, int $id)
    {
        $validated = $request->validated();
        $isApproved = $validated['action'] === 'approve';

        try {
            DB::transaction(function () use ($request, $id, $isApproved, $validated): void {
                $batch = DepartmentMonthlyAssignmentBatch::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($batch->status !== DepartmentMonthlyAssignmentBatch::STATUS_SUBMITTED
                    || $batch->current_step !== DepartmentMonthlyAssignmentBatch::STEP_TRAINING_OFFICE_REVIEW) {
                    throw new TrainingOfficeReviewDepartmentMonthlyAssignmentBatchStateException(
                        'Batch phan cong nay dang khong o trang thai cho PDT phe duyet. Vui long tai lai danh sach.'
                    );
                }

                $actorId = $request->user()->id;
                $now = now();

                $batch->fill($isApproved
                    ? [
                        'status' => DepartmentMonthlyAssignmentBatch::STATUS_APPROVED,
                        'current_step' => DepartmentMonthlyAssignmentBatch::STEP_COMPLETED,
                        'training_office_reviewed_by' => $actorId,
                        'training_office_reviewed_at' => $now,
                        'training_office_review_note' => null,
                    ]
                    : [
                        'status' => DepartmentMonthlyAssignmentBatch::STATUS_RETURNED,
                        'current_step' => DepartmentMonthlyAssignmentBatch::STEP_DRAFT,
                        'training_office_reviewed_by' => $actorId,
                        'training_office_reviewed_at' => $now,
                        'training_office_review_note' => $validated['reason'] ?? null,
                    ]);
                $batch->save();

                app(InternalNotificationService::class)->notifyDepartmentMonthlyAssignmentBatchTrainingOfficeReviewed(
                    $batch->fresh(['department', 'submittedBy']),
                    $request->user(),
                    $isApproved
                );
            });
        } catch (TrainingOfficeReviewDepartmentMonthlyAssignmentBatchStateException $exception) {
            return $this->errorResponse($request, $exception->getMessage(), 422);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Khong the xu ly phe duyet batch phan cong. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $id, $isApproved);
    }

    private function successResponse(TrainingOfficeReviewDepartmentMonthlyAssignmentBatchRequest $request, int $batchId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Batch phan cong da duoc PDT phe duyet.'
            : 'Batch phan cong da bi PDT tra ve cho Khoa.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'batch_id' => $batchId,
            ]);
        }

        return redirect()
            ->route('department-monthly-assignment-batches.show', $batchId)
            ->with('success', $message);
    }

    private function errorResponse(TrainingOfficeReviewDepartmentMonthlyAssignmentBatchRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}

final class TrainingOfficeReviewDepartmentMonthlyAssignmentBatchStateException extends \RuntimeException
{
}
