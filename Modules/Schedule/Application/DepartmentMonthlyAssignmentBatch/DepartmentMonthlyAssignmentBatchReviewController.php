<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;

class DepartmentMonthlyAssignmentBatchReviewController extends Controller
{
    public function __construct(
        private ApproveDepartmentMonthlyAssignmentBatch $approveBatch,
        private ReturnDepartmentMonthlyAssignmentBatch $returnBatch
    ) {}

    public function approve(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $batch = DepartmentMonthlyAssignmentBatch::query()->findOrFail($id);
        $this->authorizeReview($request->user());

        try {
            $batch = $this->approveBatch->handle($batch, $request->user());
        } catch (ValidationException $exception) {
            if (! $request->expectsJson()) {
                return back()
                    ->withErrors($exception->errors())
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => $this->firstValidationMessage($exception),
                'errors' => $exception->errors(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return redirect()
                ->route('department-monthly-assignment-batches.show', $batch->id)
                ->with('success', 'Da duyet batch tong hop.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da duyet batch tong hop.',
            'batch_id' => $batch->id,
            'status' => $batch->status,
        ]);
    }

    public function returnBatch(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $batch = DepartmentMonthlyAssignmentBatch::query()->findOrFail($id);
        $this->authorizeReview($request->user());

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $batch = $this->returnBatch->handle($batch, $request->user(), $validated['review_note']);
        } catch (ValidationException $exception) {
            if (! $request->expectsJson()) {
                return back()
                    ->withErrors($exception->errors())
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => $this->firstValidationMessage($exception),
                'errors' => $exception->errors(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return redirect()
                ->route('department-monthly-assignment-batches.show', $batch->id)
                ->with('success', 'Da tra batch tong hop ve khoa.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da tra batch tong hop ve khoa.',
            'batch_id' => $batch->id,
            'status' => $batch->status,
        ]);
    }

    private function authorizeReview(?User $user): void
    {
        if (! $user || (! $user->isTrainingOffice() && ! $user->isAdmin())) {
            abort(403);
        }
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
