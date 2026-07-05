<?php

namespace Modules\Schedule\Application\TeachingSupportChangeRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\TeachingSupportChangeRequest;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Training\Models\Department;

class TeachingSupportChangeRequestController extends Controller
{
    public function __construct(
        private TeachingSupportChangeRequestService $service
    ) {}

    public function create(Request $request, int $id): Response|JsonResponse
    {
        $supportRequest = TeachingSupportRequest::query()
            ->with([
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.room',
                'items.scheduleSlot.teacher',
                'items.assignedTeacher',
                'items.changeItems',
                'auditLogs.actor',
            ])
            ->findOrFail($id);

        $canCreate = $this->service->canCreateChangeRequest($supportRequest, $request->user());
        $canWithdraw = $this->service->canWithdrawRequest($supportRequest, $request->user());

        if ($canWithdraw) {
            $this->authorize('withdraw', $supportRequest);
        } elseif ($canCreate) {
            $this->authorize('create', [TeachingSupportChangeRequest::class, $supportRequest]);
        } else {
            $this->authorize('create', [TeachingSupportChangeRequest::class, $supportRequest]);
        }

        return response()->view('schedule::teaching-support-change-request.create', [
            'supportRequest' => $supportRequest,
            'changePayload' => $this->service->buildCreatePayload($supportRequest, $request->user()),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $supportRequest = TeachingSupportRequest::query()->findOrFail($id);
        $this->authorize('create', [TeachingSupportChangeRequest::class, $supportRequest]);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $changeRequest = $this->service->createChangeRequest($supportRequest, $request->user(), [
                'reason' => (string) $validated['reason'],
            ]);
        } catch (ValidationException $exception) {
            if (! $request->expectsJson()) {
                return back()->withErrors($exception->errors())->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => $this->firstValidationMessage($exception),
                'errors' => $exception->errors(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return redirect()
                ->route('teaching-support-change-requests.show', $changeRequest->id)
                ->with('success', 'Da tao phieu de nghi huy yeu cau ho tro.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da tao phieu de nghi huy yeu cau ho tro.',
            'change_request_id' => $changeRequest->id,
        ]);
    }

    public function show(Request $request, int $id): Response|JsonResponse
    {
        $changeRequest = TeachingSupportChangeRequest::query()
            ->with([
                'teachingSupportRequest.requestingDepartment',
                'teachingSupportRequest.proposedSupportingDepartment',
                'teachingSupportRequest.assignedSupportingDepartment',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.room',
                'items.scheduleSlot.teacher',
                'items.supportRequestItem.assignedTeacher',
                'items.previousTeacher',
                'auditLogs.actor',
                'auditLogs.actorDepartment',
                'auditLogs.requestItem',
                'auditLogs.scheduleSlot',
                'auditLogs.changeRequest',
            ])
            ->findOrFail($id);

        $this->authorize('view', $changeRequest);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'change_request' => $changeRequest,
            ]);
        }

        return response()->view('schedule::teaching-support-change-request.show', [
            'changeRequest' => $changeRequest,
            'canProcess' => $request->user()?->can('process', $changeRequest) ?? false,
            'canConfirm' => $request->user()?->can('confirm', $changeRequest) ?? false,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function review(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $changeRequest = TeachingSupportChangeRequest::query()->findOrFail($id);
        $this->authorize('process', $changeRequest);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'pdt_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (blank($validated['pdt_note'] ?? null)) {
            throw ValidationException::withMessages([
                'pdt_note' => (string) $validated['action'] === 'approve'
                    ? 'Vui long nhap ly do khi duyet phieu huy yeu cau ho tro.'
                    : 'Vui long nhap ly do khi tu choi phieu huy yeu cau ho tro.',
            ]);
        }

        try {
            $changeRequest = $this->service->reviewChangeRequest(
                $changeRequest,
                $request->user(),
                (string) $validated['action'],
                isset($validated['pdt_note']) ? trim((string) $validated['pdt_note']) : null
            );
        } catch (ValidationException $exception) {
            if (! $request->expectsJson()) {
                return back()->withErrors($exception->errors())->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => $this->firstValidationMessage($exception),
                'errors' => $exception->errors(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return redirect()
                ->route('teaching-support-change-requests.show', $changeRequest->id)
                ->with('success', 'Da xu ly phieu de nghi huy yeu cau ho tro.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da xu ly phieu de nghi huy yeu cau ho tro.',
            'status' => $changeRequest->status,
        ]);
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            if ($messages !== []) {
                return (string) $messages[0];
            }
        }

        return 'Validation failed.';
    }
}
