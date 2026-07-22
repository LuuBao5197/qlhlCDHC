<?php

namespace Modules\Schedule\Application\SubmitMonthlyAssignmentDossier;

use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\BuildOrRefreshMonthlyAssignmentDossier\BuildOrRefreshMonthlyAssignmentDossier;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Throwable;

class SubmitMonthlyAssignmentDossierHandler
{
    public function __construct(
        private BuildOrRefreshMonthlyAssignmentDossier $buildOrRefreshDossier
    ) {}

    /**
     * Handle submitting a monthly assignment dossier to Phong Dao tao leadership for review.
     */
    public function handle(SubmitMonthlyAssignmentDossierRequest $request, int $id)
    {
        $dossier = MonthlyAssignmentDossier::query()->findOrFail($id);

        if (! in_array($dossier->status, ['draft', 'returned'], true)) {
            return $this->errorResponse($request, 'Ho so nay khong the gui tu trang thai hien tai.', 422);
        }

        try {
            DB::transaction(function () use ($request, $dossier): void {
                $refreshed = $this->buildOrRefreshDossier->handle(
                    (int) $dossier->month,
                    (int) $dossier->year,
                    $request->user()
                );

                $now = now();
                $actorId = $request->user()->id;

                $refreshed->update([
                    'submitted_by' => $actorId,
                    'submitted_at' => $now,
                    'status' => MonthlyAssignmentDossier::STATUS_SUBMITTED,
                    'current_step' => MonthlyAssignmentDossier::STEP_TRAINING_OFFICE_REVIEW,
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => MonthlyAssignmentDossier::class,
                    'entity_id' => $refreshed->id,
                ]);

                $approvalRequest->fill([
                    'submitted_by' => $actorId,
                    'current_step' => MonthlyAssignmentDossier::STEP_TRAINING_OFFICE_REVIEW,
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

                app(InternalNotificationService::class)->notifyMonthlyAssignmentDossierSubmitted($refreshed, $request->user());
            });
        } catch (ValidationException $exception) {
            return $this->errorResponse($request, $this->firstValidationMessage($exception), 422);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                'Khong the gui ho so phan cong thang. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $id);
    }

    private function successResponse(SubmitMonthlyAssignmentDossierRequest $request, int $dossierId)
    {
        $message = 'Ho so phan cong thang da duoc gui len Lanh dao Phong Dao tao de duyet.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'dossier_id' => $dossierId,
            ]);
        }

        return redirect()->route('monthly-assignment-dossiers.show', $dossierId)
            ->with('success', $message);
    }

    private function errorResponse(SubmitMonthlyAssignmentDossierRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        $errors = $exception->errors();

        foreach ($errors as $messages) {
            if ($messages !== []) {
                return (string) $messages[0];
            }
        }

        return 'Validation failed.';
    }
}
