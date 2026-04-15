<?php

namespace Modules\Schedule\Application\ReviewChangeRequest;

use Illuminate\Support\Facades\DB;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use Modules\Training\Models\ChangeRequest;
use Throwable;

class ReviewChangeRequestHandler
{
    /**
     * Handle review for a schedule change request.
     */
    public function handle(ReviewChangeRequestRequest $request, int $id)
    {
        $changeRequest = ChangeRequest::query()->with('scheduleSlot')->findOrFail($id);
        $validated = $request->validated();
        $isApproved = $validated['action'] === 'approve';

        if ($changeRequest->status !== 'pending') {
            return $this->errorResponse($request, 'This change request has already been processed.', 422);
        }

        try {
            DB::transaction(function () use ($request, $changeRequest, $validated, $isApproved): void {
                $now = now();
                $actorId = $request->user()->id;
                $applyChanges = (bool) ($validated['apply_changes'] ?? true);

                if ($isApproved && $applyChanges) {
                    $this->applyNewPayloadToSlot($changeRequest);
                }

                $changeRequest->update([
                    'status' => $isApproved ? 'approved' : 'rejected',
                    'resolved_at' => $now,
                ]);

                $approvalRequest = ApprovalRequest::query()->firstOrNew([
                    'entity_type' => ChangeRequest::class,
                    'entity_id' => $changeRequest->id,
                ]);

                if (! $approvalRequest->exists) {
                    $approvalRequest->submitted_by = $changeRequest->requested_by;
                    $approvalRequest->submitted_at = $changeRequest->submitted_at ?? $now;
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
                'Could not review change request. ' . $exception->getMessage(),
                500
            );
        }

        return $this->successResponse($request, $changeRequest->id, $isApproved);
    }

    private function applyNewPayloadToSlot(ChangeRequest $changeRequest): void
    {
        $slot = $changeRequest->scheduleSlot;

        if ($slot === null || ! is_array($changeRequest->new_payload)) {
            return;
        }

        $allowedKeys = [
            'class_id',
            'teacher_id',
            'subject_id',
            'subject_lesson_id',
            'room_id',
            'date',
            'day_of_week',
            'period',
            'period_number',
            'subject',
            'content',
            'slot_status',
            'actual_content',
            'note',
        ];

        $payload = array_intersect_key($changeRequest->new_payload, array_flip($allowedKeys));

        if ($payload === []) {
            return;
        }

        $slot->fill($payload);

        if ($slot->isDirty()) {
            $slot->save();
        }
    }

    private function buildComment(array $validated): ?string
    {
        $parts = array_filter([
            $validated['reason'] ?? null,
            $validated['comment'] ?? null,
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function successResponse(ReviewChangeRequestRequest $request, int $changeRequestId, bool $isApproved)
    {
        $message = $isApproved
            ? 'Change request approved successfully.'
            : 'Change request rejected successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'change_request_id' => $changeRequestId,
            ]);
        }

        return redirect()->route('schedule.index')->with('success', $message);
    }

    private function errorResponse(ReviewChangeRequestRequest $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
