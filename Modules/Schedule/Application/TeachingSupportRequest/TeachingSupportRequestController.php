<?php

namespace Modules\Schedule\Application\TeachingSupportRequest;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Schedule\Models\TeachingSupportRequestItem;
use Modules\Training\Models\Department;
use Modules\Schedule\Application\Shared\TeacherAvailabilityService;
use Modules\Schedule\Application\TeachingSupportChangeRequest\TeachingSupportChangeRequestService;

class TeachingSupportRequestController extends Controller
{
    public function __construct(
        private TeachingSupportRequestService $service,
        private TeacherAvailabilityService $teacherAvailabilityService,
        private TeachingSupportChangeRequestService $changeRequestService
    ) {}

    public function store(StoreTeachingSupportRequestRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $this->authorize('create', TeachingSupportRequest::class);

        $monthlySchedule = MonthlySchedule::query()
            ->with(['plan', 'trainingClass.department', 'scheduleSlots.subjectModel.department'])
            ->findOrFail($id);

        try {
            $requestModel = $this->service->createRequest(
                $monthlySchedule,
                $request->user(),
                (int) $request->validated('supporting_department_id'),
                (string) ($request->validated('request_note') ?? ''),
                $request->validated('slot_ids', [])
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
                ->route('monthly-schedule.assignment', $monthlySchedule->id)
                ->with('success', 'Da gui de nghi ho tro lien khoa.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da gui de nghi ho tro lien khoa.',
            'request_id' => $requestModel->id,
        ]);
    }

    public function withdraw(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $requestModel = TeachingSupportRequest::query()->findOrFail($id);
        $this->authorize('withdraw', $requestModel);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $requestModel = $this->service->withdrawRequest(
                $requestModel,
                $request->user(),
                trim((string) $validated['reason'])
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
                ->route('teaching-support-requests.show', $requestModel->id)
                ->with('success', 'Da thu hoi yeu cau ho tro.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da thu hoi yeu cau ho tro.',
            'status' => $requestModel->status,
        ]);
    }

    public function index(Request $request): Response|JsonResponse
    {
        $this->authorizeQueue($request->user());

        $filters = $this->resolveIndexFilters($request);

        $query = TeachingSupportRequest::query()
            ->with([
                'assignmentBatch.department',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher',
            ])
            ->withCount('items');

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['department_id'] !== null) {
            $query->where(function ($nested) use ($filters): void {
                $nested->where('requesting_department_id', $filters['department_id'])
                    ->orWhere('proposed_supporting_department_id', $filters['department_id'])
                    ->orWhere('assigned_supporting_department_id', $filters['department_id']);
            });
        }

        $requests = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('pdt_processed_at')
            ->paginate(15)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $requests,
            ]);
        }

        return response()->view('schedule::teaching-support-request.index', [
            'requests' => $requests,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'statusTabs' => [
                'pending_pdt' => 'Chờ PDT duyệt',
                'assigned_to_department' => 'Đã duyệt',
                'department_assigning' => 'Khoa đang phân công',
                'completed' => 'Hoàn tất',
                'returned' => 'Đã từ chối',
                'cancelled' => 'Đã hủy',
                'all' => 'Tất cả',
            ],
        ]);
    }

    public function show(Request $request, int $id): Response|JsonResponse
    {
        $requestModel = TeachingSupportRequest::query()
            ->with([
                'assignmentBatch.department',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment.teachers',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.monthlySchedule.plan',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.room',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher.department',
                'items.assignedBy',
                'changeRequests.requestingDepartment',
                'changeRequests.proposedSupportingDepartment',
                'changeRequests.assignedSupportingDepartment',
                'changeRequests.submittedBy',
                'changeRequests.pdtProcessedBy',
                'changeRequests.items.scheduleSlot.trainingClass',
                'changeRequests.items.scheduleSlot.subjectModel.department',
                'changeRequests.items.scheduleSlot.subjectLesson',
                'changeRequests.items.scheduleSlot.room',
                'changeRequests.items.scheduleSlot.teacher',
                'changeRequests.items.supportRequestItem.assignedTeacher',
                'changeRequests.items.previousTeacher',
                'changeRequests.auditLogs.actor',
                'changeRequests.auditLogs.actorDepartment',
                'changeRequests.auditLogs.requestItem',
                'changeRequests.auditLogs.scheduleSlot',
                'auditLogs.actor',
                'auditLogs.actorDepartment',
                'auditLogs.changeRequest',
                'auditLogs.requestItem',
                'auditLogs.scheduleSlot',
            ])
            ->findOrFail($id);

        $this->authorize('view', $requestModel);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'request' => $requestModel,
            ]);
        }

        return response()->view('schedule::teaching-support-request.show', [
            'requestModel' => $requestModel,
            'canProcess' => $request->user()?->can('process', $requestModel) ?? false,
            'canConfirm' => $request->user()?->isDepartmentStaff()
                && (int) $requestModel->assigned_supporting_department_id === (int) $request->user()?->department_id,
            'changeRequestPayload' => $this->changeRequestService->buildDetailPayload($requestModel, $request->user()),
            'canCreateChangeRequest' => $this->changeRequestService->canCreateChangeRequest($requestModel, $request->user()),
            'canWithdrawDirectly' => $request->user() ? $this->changeRequestService->canWithdrawRequest($requestModel, $request->user()) : false,
        ]);
    }

    public function monthlyAssignmentModal(Request $request, int $id): Response|JsonResponse
    {
        $monthlySchedule = MonthlySchedule::query()
            ->with(['plan', 'trainingClass.department'])
            ->findOrFail($id);

        $modalData = $this->service->buildDepartmentSupportRequestModalData(
            $monthlySchedule,
            $request->user(),
            [
                'q' => (string) $request->query('q', ''),
                'status' => (string) $request->query('status', 'all'),
                'item_status' => (string) $request->query('item_status', 'all'),
                'per_page' => $request->integer('per_page', 10),
                'page' => $request->integer('page', 1),
            ]
        );

        if ($modalData['scope'] === null) {
            abort(403);
        }

        return response()->view('schedule::teaching-support-request.assignment-list-fragment', [
            'monthlySchedule' => $monthlySchedule,
            'requests' => $modalData['requests'],
            'filters' => $modalData['filters'],
            'requestStatusOptions' => $modalData['request_status_options'],
            'itemStatusOptions' => $modalData['item_status_options'],
            'requestStatusLabels' => [
                'pending_pdt' => 'Chờ Phòng Đào tạo',
                'assigned_to_department' => 'Phòng Đào tạo đã duyệt',
                'department_assigning' => 'Khoa đang phân công',
                'completed' => 'Hoàn tất',
                'returned' => 'Đã trả về',
                'cancelled' => 'Đã hủy',
            ],
            'itemStatusLabels' => [
                'pending' => 'Chờ xử lý',
                'assigned' => 'Đã giao giảng viên',
                'confirmed' => 'Đã xác nhận',
                'rejected' => 'Từ chối',
            ],
            'modalUrl' => route('monthly-schedule.teaching-support-requests.modal', $monthlySchedule->id),
        ]);
    }

    public function review(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $requestModel = TeachingSupportRequest::query()->findOrFail($id);
        $this->authorize('process', $requestModel);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'pdt_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $requestModel = $this->service->processRequest(
                $requestModel,
                $request->user(),
                (string) $validated['action'],
                (string) ($validated['pdt_note'] ?? '')
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
                ->route('teaching-support-requests.show', $requestModel->id)
                ->with('success', 'Da xu ly de nghi ho tro.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da xu ly de nghi ho tro.',
            'status' => $requestModel->status,
        ]);
    }

    public function inbox(Request $request): Response|JsonResponse
    {
        $this->authorizeInbox($request->user());

        $departmentId = (int) $request->user()->department_id;

        $requests = TeachingSupportRequest::query()
            ->with([
                'assignmentBatch.department',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment.teachers',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.monthlySchedule.plan',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.subjectLesson',
                'items.scheduleSlot.room',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher.department',
                'items.assignedBy',
            ])
            ->where('assigned_supporting_department_id', $departmentId)
            ->whereIn('status', [
                TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
            ])
            ->orderByDesc('pdt_processed_at')
            ->get();

        $teacherAvailabilityMap = [];
        $supportTeachers = collect();
        if ($requests->isNotEmpty()) {
            $supportTeachers = $requests->first()?->assignedSupportingDepartment?->teachers?->values() ?? collect();

            foreach ($requests as $requestModel) {
                foreach ($requestModel->items as $item) {
                    foreach ($supportTeachers as $teacher) {
                        $teacherAvailabilityMap[$item->id][$teacher->id] = $this->teacherAvailabilityService->isTeacherAvailableForSlot(
                            (int) $teacher->id,
                            $item->scheduleSlot,
                            $item->scheduleSlot?->id
                        );
                    }
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'requests' => $requests,
            ]);
        }

        return response()->view('schedule::teaching-support-request.inbox', [
            'requests' => $requests,
            'teacherAvailabilityMap' => $teacherAvailabilityMap,
        ]);
    }

    public function confirm(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $requestModel = TeachingSupportRequest::query()->findOrFail($id);
        $this->authorize('confirm', $requestModel);

        $validated = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.request_item_id' => ['required', 'integer', 'exists:teaching_support_request_items,id'],
            'assignments.*.teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'assignments.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $requestModel = $this->service->confirmAssignments(
                $requestModel,
                $request->user(),
                $validated['assignments']
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
                ->route('teaching-support-requests.inbox')
                ->with('success', 'Da xac nhan phan cong ho tro.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da xac nhan phan cong ho tro.',
            'status' => $requestModel->status,
        ]);
    }

    public function assignItem(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $item = TeachingSupportRequestItem::query()
            ->with(['request'])
            ->findOrFail($id);

        $this->authorize('confirm', $item->request);

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $requestModel = $this->service->assignSupportItem(
                $item,
                $request->user(),
                (int) $validated['teacher_id'],
                isset($validated['note']) ? trim((string) $validated['note']) : null
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
            return back()->with('success', 'Da cap nhat giang vien ho tro.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da cap nhat giang vien ho tro.',
            'status' => $requestModel->status,
        ]);
    }

    private function authorizeQueue(?User $user): void
    {
        if (! $user || (! $user->isAdmin() && ! $user->isTrainingOffice())) {
            abort(403);
        }
    }

    private function authorizeInbox(?User $user): void
    {
        if (! $user || ! $user->isDepartmentStaff()) {
            abort(403);
        }
    }

    /**
     * @return array{status:string,department_id:int|null}
     */
    private function resolveIndexFilters(Request $request): array
    {
        $status = (string) $request->query('status', 'pending_pdt');
        $allowedStatuses = [
            TeachingSupportRequest::STATUS_PENDING_PDT,
            TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
            TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
            TeachingSupportRequest::STATUS_COMPLETED,
            TeachingSupportRequest::STATUS_RETURNED,
            TeachingSupportRequest::STATUS_CANCELLED,
            'all',
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = TeachingSupportRequest::STATUS_PENDING_PDT;
        }

        $departmentId = $request->integer('department_id');
        $departmentId = $departmentId > 0 ? $departmentId : null;

        return [
            'status' => $status,
            'department_id' => $departmentId,
        ];
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
