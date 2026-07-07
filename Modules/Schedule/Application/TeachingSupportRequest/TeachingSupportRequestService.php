<?php

namespace Modules\Schedule\Application\TeachingSupportRequest;

use App\Services\InternalNotificationService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\AssignMonthlySchedule\MonthlyAssignmentScopeResolver;
use Modules\Schedule\Application\Shared\TeachingSupportAuditService;
use Modules\Schedule\Application\Shared\TeacherAvailabilityService;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Schedule\Models\TeachingSupportRequestItem;
use Modules\Training\Models\Department;
use Modules\Training\Models\Teacher;

class TeachingSupportRequestService
{
    private const ACTIVE_REQUEST_STATUSES = [
        TeachingSupportRequest::STATUS_PENDING_PDT,
        TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
        TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
    ];

    public function __construct(
        private MonthlyAssignmentScopeResolver $scopeResolver,
        private TeacherAvailabilityService $teacherAvailabilityService,
        private TeachingSupportAuditService $auditService
    ) {}

    public function buildMonthlyAssignmentSupportMeta(MonthlySchedule $monthlySchedule, ?User $actor = null): array
    {
        $scope = $this->scopeResolver->resolve($monthlySchedule, $actor);
        if ($scope === null) {
            return [
                'slot_meta' => [],
                'support_department_options' => [],
                'summary' => [],
                'can_create_request' => false,
            ];
        }

        $slots = ScheduleSlot::query()
            ->with([
                'teacher',
                'scheduleSlotGroup',
                'teachingSupportRequestItem.request.requestingDepartment',
                'teachingSupportRequestItem.request.proposedSupportingDepartment',
                'teachingSupportRequestItem.request.assignedSupportingDepartment',
                'teachingSupportRequestItem.assignedTeacher',
            ])
            ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
            ->where('slot_type', 'subject')
            ->when(
                $scope['department_subject_ids'] !== [],
                fn ($query) => $query->whereIn('subject_id', $scope['department_subject_ids'])
            )
            ->orderBy('date')
            ->orderBy('period_number')
            ->orderBy('monthly_schedule_id')
            ->orderBy('class_id')
            ->orderBy('id')
            ->get();

        $requestItems = TeachingSupportRequestItem::query()
            ->with([
                'request.requestingDepartment',
                'request.proposedSupportingDepartment',
                'request.assignedSupportingDepartment',
                'assignedTeacher',
                'scheduleSlot.scheduleSlotGroup',
            ])
            ->whereIn('schedule_slot_id', $slots->pluck('id')->all())
            ->whereHas('request', function ($query): void {
                $query->whereIn('status', array_merge(self::ACTIVE_REQUEST_STATUSES, [
                    TeachingSupportRequest::STATUS_COMPLETED,
                    TeachingSupportRequest::STATUS_RETURNED,
                ]));
            })
            ->get()
            ->keyBy('schedule_slot_id');

        $slotMeta = [];
        foreach ($slots as $slot) {
            $item = $requestItems->get($slot->id);
            $request = $item?->request;
            if (! $request instanceof TeachingSupportRequest) {
                continue;
            }

            $slotMeta[$slot->id] = $this->formatSupportMeta($slot, $item, $request);
        }

        $summary = [
            'pending_pdt' => 0,
            'assigned_to_department' => 0,
            'department_assigning' => 0,
            'completed' => 0,
            'returned' => 0,
        ];

        foreach ($slotMeta as $meta) {
            $status = $meta['status'] ?? null;
            if ($status !== null && array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        $supportDepartmentOptions = Department::query()
            ->where('id', '!=', (int) $scope['department_id'])
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (Department $department): array => [
                'id' => (int) $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ])
            ->values()
            ->all();

        return [
            'slot_meta' => $slotMeta,
            'support_department_options' => $supportDepartmentOptions,
            'summary' => $summary,
            'can_create_request' => $this->canCreateRequestFromScope($scope)
                && $slots->contains(fn (ScheduleSlot $slot): bool => ($slot->teacher_id === null)
                    && ($slot->assignment_source ?? 'internal') === 'internal'
                    && $slot->room_id !== null),
        ];
    }

    public function buildDepartmentSupportWorkload(MonthlySchedule $monthlySchedule, ?User $actor = null): array
    {
        $scope = $this->scopeResolver->resolve($monthlySchedule, $actor);
        if ($scope === null) {
            return [
                'rows' => collect(),
                'teacher_availability_map' => [],
                'summary' => [
                    'total' => 0,
                    'confirmed' => 0,
                    'assigned_to_department' => 0,
                    'department_assigning' => 0,
                    'completed' => 0,
                ],
            ];
        }

        $departmentId = (int) $scope['department_id'];
        $teacherAvailabilityMap = [];
        $supportTeachers = Teacher::query()
            ->where('department_id', (int) $departmentId)
            ->orderBy('name')
            ->get(['id']);

        $rows = TeachingSupportRequestItem::query()
            ->with([
                'request.assignmentBatch',
                'request.requestingDepartment',
                'request.assignedSupportingDepartment',
                'request.proposedSupportingDepartment',
                'assignedTeacher.department',
                'assignedBy',
                'scheduleSlot.trainingClass.department',
                'scheduleSlot.subjectModel.department',
                'scheduleSlot.subjectLesson',
                'scheduleSlot.room',
                'scheduleSlot.teacher',
                'scheduleSlot.scheduleSlotGroup',
            ])
            ->whereHas('request', function ($query) use ($departmentId, $monthlySchedule): void {
                $query->where('assigned_supporting_department_id', $departmentId)
                    ->whereIn('status', [
                        TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                        TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                        TeachingSupportRequest::STATUS_COMPLETED,
                    ])
                    ->whereHas('assignmentBatch', function ($nested) use ($monthlySchedule): void {
                        $nested->where('month', (int) $monthlySchedule->month)
                            ->where('year', (int) $monthlySchedule->year);
                    });
            })
            ->get()
            ->sortBy([
                fn (TeachingSupportRequestItem $item) => $item->scheduleSlot?->date?->timestamp ?? 0,
                fn (TeachingSupportRequestItem $item) => (int) ($item->scheduleSlot?->period_number ?? 0),
                fn (TeachingSupportRequestItem $item) => (int) ($item->scheduleSlot?->class_id ?? 0),
                fn (TeachingSupportRequestItem $item) => (int) $item->id,
            ])
            ->values()
            ->map(function (TeachingSupportRequestItem $item) use (&$teacherAvailabilityMap, $supportTeachers, $departmentId): array {
                $request = $item->request;
                $slot = $item->scheduleSlot;
                $requestItemId = (int) $item->id;

                if ($slot instanceof ScheduleSlot) {
                    foreach ($supportTeachers as $teacher) {
                        $teacherAvailabilityMap[$requestItemId][(int) $teacher->id] = $this->teacherAvailabilityService->isTeacherAvailableForSlot(
                            (int) $teacher->id,
                            $slot,
                            $slot->id
                        );
                    }
                }

                return [
                    'request_id' => (int) $item->request_id,
                    'request_item_id' => $requestItemId,
                    'request_status' => $request?->status ?? null,
                    'request_status_label' => $this->labelForRequestStatus($request?->status),
                    'item_status' => $item->status,
                    'item_status_label' => $this->labelForItemStatus($item->status),
                    'requesting_department_id' => (int) ($request?->requesting_department_id ?? 0),
                    'requesting_department_name' => $request?->requestingDepartment?->name ?? '-',
                    'assigned_department_id' => (int) ($request?->assigned_supporting_department_id ?? 0),
                    'assigned_department_name' => $request?->assignedSupportingDepartment?->name ?? '-',
                    'proposed_department_name' => $request?->proposedSupportingDepartment?->name ?? '-',
                    'assigned_teacher_id' => (int) ($item->assigned_teacher_id ?? 0),
                    'schedule_slot_group_id' => (int) ($slot?->schedule_slot_group_id ?? 0),
                    'schedule_slot_group_status' => $slot?->scheduleSlotGroup?->status ?? null,
                    'is_merged_group' => $slot?->scheduleSlotGroup?->status === 'active'
                        && is_numeric($slot?->schedule_slot_group_id),
                    'class_code' => $slot?->trainingClass?->code ?? '-',
                    'class_name' => $slot?->trainingClass?->name ?? '-',
                    'subject_code' => $slot?->subjectModel?->code ?? '-',
                    'subject_name' => $slot?->subjectModel?->name ?? '-',
                    'date_label' => $slot?->date?->format('d/m/Y') ?? '-',
                    'period_number' => $slot?->period_number,
                    'room_code' => $slot?->room?->code ?? '-',
                    'room_name' => $slot?->room?->name ?? '-',
                    'teacher_name' => $item->assignedTeacher?->name ?? $slot?->teacher?->name ?? '-',
                    'assignment_source' => $slot?->assignment_source ?? 'department_support',
                    'can_edit' => in_array($request?->status, [
                        TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                        TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                    ], true)
                        && (int) ($request?->assigned_supporting_department_id ?? 0) === (int) $departmentId,
                    'read_only' => ! in_array($request?->status, [
                        TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                        TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                    ], true)
                        || (int) ($request?->assigned_supporting_department_id ?? 0) !== (int) $departmentId,
                    'support_badge_label' => 'Hỗ trợ liên khoa',
                    'confirm_url' => route('teaching-support-request-items.assign', $item->id),
                    'request_note' => $request?->request_note,
                    'pdt_note' => $request?->pdt_note,
                ];
            });

        $mergedGroupCounts = $rows
            ->filter(fn (array $row): bool => (int) ($row['schedule_slot_group_id'] ?? 0) > 0 && ($row['schedule_slot_group_status'] ?? null) === 'active')
            ->groupBy(fn (array $row): string => (string) ($row['schedule_slot_group_id'] ?? 0))
            ->map(static fn (Collection $group): int => $group->count());

        $rows = $rows->map(function (array $row) use ($mergedGroupCounts): array {
            $groupId = (int) ($row['schedule_slot_group_id'] ?? 0);
            $groupCount = (int) ($mergedGroupCounts[$groupId] ?? 0);

            $row['merge_group_count'] = $groupCount;
            $row['merge_group_label'] = ! empty($row['is_merged_group'])
                ? 'Tiết ghép' . ($groupCount > 1 ? ' - ' . $groupCount . ' lớp' : '')
                : null;
            $row['merge_group_note'] = ! empty($row['is_merged_group'])
                ? 'Giảng viên áp dụng cho toàn bộ nhóm ghép.'
                : null;

            return $row;
        });

        return [
            'rows' => $rows,
            'teacher_availability_map' => $teacherAvailabilityMap,
            'summary' => [
                'total' => $rows->count(),
                'confirmed' => $rows->where('item_status', TeachingSupportRequestItem::STATUS_CONFIRMED)->count(),
                'assigned_to_department' => $rows->where('request_status', TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT)->count(),
                'department_assigning' => $rows->where('request_status', TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING)->count(),
                'completed' => $rows->where('request_status', TeachingSupportRequest::STATUS_COMPLETED)->count(),
            ],
        ];
    }

    /**
     * @param array{q?:string,status?:string,item_status?:string,per_page?:int,page?:int} $filters
     * @return array{
     *     requests:LengthAwarePaginator,
     *     filters:array{q:string,status:string,item_status:string,per_page:int},
     *     scope:array<string, mixed>|null,
     *     request_status_options:array<string, string>,
     *     item_status_options:array<string, string>
     * }
     */
    public function buildDepartmentSupportRequestModalData(MonthlySchedule $monthlySchedule, ?User $actor = null, array $filters = []): array
    {
        $scope = $this->scopeResolver->resolve($monthlySchedule, $actor);
        $normalizedFilters = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'status' => (string) ($filters['status'] ?? 'all'),
            'item_status' => (string) ($filters['item_status'] ?? 'all'),
            'per_page' => max(5, min(25, (int) ($filters['per_page'] ?? 10))),
        ];

        $requestStatusOptions = [
            'all' => 'Tat ca',
            TeachingSupportRequest::STATUS_PENDING_PDT => 'Cho PDT',
            TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT => 'PDT da duyet',
            TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING => 'Khoa dang phan cong',
            TeachingSupportRequest::STATUS_COMPLETED => 'Hoan tat',
            TeachingSupportRequest::STATUS_RETURNED => 'Da tu choi',
            TeachingSupportRequest::STATUS_CANCELLED => 'Da huy',
        ];

        $itemStatusOptions = [
            'all' => 'Tat ca',
            TeachingSupportRequestItem::STATUS_PENDING => 'Cho xu ly',
            TeachingSupportRequestItem::STATUS_ASSIGNED => 'Da giao giang vien',
            TeachingSupportRequestItem::STATUS_CONFIRMED => 'Da xac nhan',
            TeachingSupportRequestItem::STATUS_REJECTED => 'Tu choi',
        ];

        if ($scope === null) {
            return [
                'requests' => new LengthAwarePaginator([], 0, $normalizedFilters['per_page'], 1, [
                    'path' => url()->current(),
                    'pageName' => 'page',
                ]),
                'filters' => $normalizedFilters,
                'scope' => null,
                'request_status_options' => $requestStatusOptions,
                'item_status_options' => $itemStatusOptions,
            ];
        }

        $requestStatus = array_key_exists($normalizedFilters['status'], $requestStatusOptions)
            ? $normalizedFilters['status']
            : 'all';
        $itemStatus = array_key_exists($normalizedFilters['item_status'], $itemStatusOptions)
            ? $normalizedFilters['item_status']
            : 'all';

        $query = TeachingSupportRequestItem::query()
            ->select('teaching_support_request_items.*')
            ->join('schedule_slots', 'teaching_support_request_items.schedule_slot_id', '=', 'schedule_slots.id')
            ->with([
                'request.assignmentBatch',
                'request.requestingDepartment',
                'request.proposedSupportingDepartment',
                'request.assignedSupportingDepartment',
                'request.submittedBy',
                'request.pdtProcessedBy',
                'scheduleSlot.trainingClass',
                'scheduleSlot.subjectModel.department',
                'scheduleSlot.subjectLesson',
                'scheduleSlot.room',
                'scheduleSlot.teacher',
                'assignedTeacher',
            ])
            ->whereHas('request', function ($requestQuery) use ($scope, $monthlySchedule, $requestStatus): void {
                $requestQuery->where('requesting_department_id', (int) $scope['department_id'])
                    ->whereHas('assignmentBatch', function ($batchQuery) use ($monthlySchedule): void {
                        $batchQuery->where('month', (int) $monthlySchedule->month)
                            ->where('year', (int) $monthlySchedule->year);
                    });

                if ($requestStatus !== 'all') {
                    $requestQuery->where('status', $requestStatus);
                }
            });

        if ($itemStatus !== 'all') {
            $query->where('teaching_support_request_items.status', $itemStatus);
        }

        if ($normalizedFilters['q'] !== '') {
            $search = $normalizedFilters['q'];
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';

            $query->where(function ($nested) use ($like, $search): void {
                $nested->where('teaching_support_request_items.note', 'like', $like)
                    ->orWhereHas('request', function ($requestQuery) use ($like, $search): void {
                        $requestQuery->where('request_note', 'like', $like)
                            ->orWhere('pdt_note', 'like', $like)
                            ->orWhereHas('requestingDepartment', function ($departmentQuery) use ($like): void {
                                $departmentQuery->where('name', 'like', $like)
                                    ->orWhere('code', 'like', $like);
                            })
                            ->orWhereHas('proposedSupportingDepartment', function ($departmentQuery) use ($like): void {
                                $departmentQuery->where('name', 'like', $like)
                                    ->orWhere('code', 'like', $like);
                            })
                            ->orWhereHas('assignedSupportingDepartment', function ($departmentQuery) use ($like): void {
                                $departmentQuery->where('name', 'like', $like)
                                    ->orWhere('code', 'like', $like);
                            })
                            ->orWhereHas('submittedBy', function ($userQuery) use ($like): void {
                                $userQuery->where('name', 'like', $like)
                                    ->orWhere('email', 'like', $like);
                            })
                            ->orWhereRaw('CAST(teaching_support_requests.id AS CHAR) LIKE ?', [$like]);
                    })
                    ->orWhereHas('scheduleSlot', function ($slotQuery) use ($like): void {
                        $slotQuery->whereHas('trainingClass', function ($classQuery) use ($like): void {
                            $classQuery->where('code', 'like', $like)
                                ->orWhere('name', 'like', $like);
                        })
                            ->orWhereHas('subjectModel', function ($subjectQuery) use ($like): void {
                                $subjectQuery->where('code', 'like', $like)
                                    ->orWhere('name', 'like', $like);
                            })
                            ->orWhereHas('room', function ($roomQuery) use ($like): void {
                                $roomQuery->where('code', 'like', $like)
                                    ->orWhere('name', 'like', $like);
                            })
                            ->orWhereRaw("DATE_FORMAT(schedule_slots.date, '%d/%m/%Y') LIKE ?", [$like])
                            ->orWhereRaw("DATE_FORMAT(schedule_slots.date, '%Y-%m-%d') LIKE ?", [$like]);
                    });
            });
        }

        $requests = $query
            ->orderBy('schedule_slots.date')
            ->orderBy('schedule_slots.period_number')
            ->orderBy('schedule_slots.class_id')
            ->orderBy('teaching_support_request_items.id')
            ->paginate($normalizedFilters['per_page'])
            ->withQueryString();

        return [
            'requests' => $requests,
            'filters' => $normalizedFilters,
            'scope' => $scope,
            'request_status_options' => $requestStatusOptions,
            'item_status_options' => $itemStatusOptions,
        ];
    }

    /**
     * @param array<int, int> $slotIds
     */
    public function createRequest(MonthlySchedule $monthlySchedule, User $actor, int $supportingDepartmentId, string $requestNote, array $slotIds): TeachingSupportRequest
    {
        return DB::transaction(function () use ($monthlySchedule, $actor, $supportingDepartmentId, $requestNote, $slotIds): TeachingSupportRequest {
            $scope = $this->scopeResolver->resolve($monthlySchedule, $actor);
            if ($scope === null) {
                throw ValidationException::withMessages([
                    'scope' => 'Khong xac dinh duoc khoa hien tai de tao de nghi ho tro.',
                ]);
            }

            if (! $actor->isDepartmentStaff() && ! $actor->isAdmin() && ! $actor->isTrainingOffice()) {
                throw ValidationException::withMessages([
                    'scope' => 'Chi khoa hoac PDT moi co the tao de nghi ho tro.',
                ]);
            }

            if ($actor->isDepartmentStaff() && (int) $actor->department_id !== (int) $scope['department_id']) {
                throw ValidationException::withMessages([
                    'scope' => 'Nguoi dung khong thuoc khoa dang thao tac.',
                ]);
            }

            $supportDepartment = Department::query()->find($supportingDepartmentId);
            if (! $supportDepartment) {
                throw ValidationException::withMessages([
                    'proposed_supporting_department_id' => 'Khoa ho tro khong ton tai.',
                ]);
            }

            if ((int) $supportDepartment->id === (int) $scope['department_id']) {
                throw ValidationException::withMessages([
                    'proposed_supporting_department_id' => 'Khoa ho tro phai khac khoa de nghi.',
                ]);
            }

            $selectedSlotIds = $this->normalizeSlotIds($slotIds);
            if ($selectedSlotIds === []) {
                throw ValidationException::withMessages([
                    'slot_ids' => 'Vui long chon it nhat 1 tiet de de nghi ho tro.',
                ]);
            }

            $existingBatch = $this->resolveAssignmentBatch($monthlySchedule, (int) $scope['department_id']);
            $slots = $this->loadRequestableSlots($scope['monthly_schedule_ids'], $scope['department_subject_ids'], $selectedSlotIds);
            if ($slots->count() !== count($selectedSlotIds)) {
                throw ValidationException::withMessages([
                    'slot_ids' => 'Co tiet khong hop le, da co giang vien hoac dang nam trong workflow ho tro.',
                ]);
            }
            $this->ensureSlotsHaveClearSubjectLessons($slots);
            $slots = $this->expandActiveMergeGroups($slots, $scope['monthly_schedule_ids'], $scope['department_subject_ids']);
            $this->ensureSlotsHaveClearSubjectLessons($slots);
            $this->ensureSlotsHaveRooms($slots);

            $this->ensureNoDuplicateActiveRequest($slots);

            $request = TeachingSupportRequest::query()->create([
                'assignment_batch_id' => $existingBatch->id,
                'requesting_department_id' => (int) $scope['department_id'],
                'proposed_supporting_department_id' => $supportDepartment->id,
                'assigned_supporting_department_id' => null,
                'status' => TeachingSupportRequest::STATUS_PENDING_PDT,
                'request_note' => $requestNote,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'pdt_processed_by' => null,
                'pdt_processed_at' => null,
                'pdt_note' => null,
            ]);

            $now = now();
            $request->items()->createMany(
                $slots->map(static fn (ScheduleSlot $slot): array => [
                    'schedule_slot_id' => $slot->id,
                    'status' => TeachingSupportRequestItem::STATUS_PENDING,
                    'assigned_teacher_id' => null,
                    'assigned_by' => null,
                    'assigned_at' => null,
                    'note' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );

            $freshRequest = $request->fresh([
                'assignmentBatch',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.room',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher',
            ]);

            $this->auditService->record([
                'request_id' => $freshRequest->id,
                'action' => 'support_request_submitted',
                'actor' => $actor,
                'old_values' => null,
                'new_values' => [
                    'request' => $this->auditService->snapshotSupportRequest($freshRequest),
                    'items' => $freshRequest->items->map(fn (TeachingSupportRequestItem $item) => $this->auditService->snapshotRequestItem($item))->all(),
                ],
                'note' => $requestNote,
                'occurred_at' => $now,
            ]);

            app(InternalNotificationService::class)->notifyTeachingSupportRequestSubmitted($freshRequest, $actor);

            return $freshRequest;
        });
    }

    public function processRequest(TeachingSupportRequest $request, User $actor, string $action, ?string $pdtNote): TeachingSupportRequest
    {
        return DB::transaction(function () use ($request, $actor, $action, $pdtNote): TeachingSupportRequest {
            $lockedRequest = TeachingSupportRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== TeachingSupportRequest::STATUS_PENDING_PDT) {
                throw ValidationException::withMessages([
                    'request' => 'Chi co the xu ly de nghi khi dang o trang thai cho PDT.',
                ]);
            }

            if ($action === 'reject') {
                $oldValues = $this->auditService->snapshotSupportRequest($lockedRequest);
                $lockedRequest->fill([
                    'status' => TeachingSupportRequest::STATUS_RETURNED,
                    'assigned_supporting_department_id' => null,
                    'pdt_processed_by' => $actor->id,
                    'pdt_processed_at' => now(),
                    'pdt_note' => $pdtNote,
                ]);
                $lockedRequest->save();

                $this->auditService->record([
                    'request_id' => $lockedRequest->id,
                    'action' => 'support_request_returned_by_pdt',
                    'actor' => $actor,
                    'old_values' => $oldValues,
                    'new_values' => $this->auditService->snapshotSupportRequest($lockedRequest),
                    'note' => $pdtNote,
                    'occurred_at' => now(),
                ]);
            } elseif ($action === 'approve') {
                $supportDepartment = Department::query()->find((int) ($lockedRequest->proposed_supporting_department_id ?? 0));
                if (! $supportDepartment) {
                    throw ValidationException::withMessages([
                        'request' => 'Khoa ho tro khong ton tai.',
                    ]);
                }

                $oldValues = $this->auditService->snapshotSupportRequest($lockedRequest);
                $lockedRequest->fill([
                    'status' => TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                    'assigned_supporting_department_id' => $supportDepartment->id,
                    'pdt_processed_by' => $actor->id,
                    'pdt_processed_at' => now(),
                    'pdt_note' => $pdtNote,
                ]);
                $lockedRequest->save();

                $this->auditService->record([
                    'request_id' => $lockedRequest->id,
                    'action' => 'support_request_approved_by_pdt',
                    'actor' => $actor,
                    'old_values' => $oldValues,
                    'new_values' => $this->auditService->snapshotSupportRequest($lockedRequest),
                    'note' => $pdtNote,
                    'occurred_at' => now(),
                ]);
            } else {
                throw ValidationException::withMessages([
                    'action' => 'Hanh dong PDT khong hop le.',
                ]);
            }

            app(InternalNotificationService::class)->notifyTeachingSupportRequestReviewed(
                $lockedRequest,
                $actor,
                $action === 'approve'
            );

            return $lockedRequest->fresh([
                'assignmentBatch',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.room',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher',
            ]);
        });
    }

    /**
     * @param array<int, array{request_item_id:int, teacher_id:int, note:?string}> $assignments
     */
    public function confirmAssignments(TeachingSupportRequest $request, User $actor, array $assignments): TeachingSupportRequest
    {
        return DB::transaction(function () use ($request, $actor, $assignments): TeachingSupportRequest {
            $lockedRequest = TeachingSupportRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT
                && $lockedRequest->status !== TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING) {
                throw ValidationException::withMessages([
                    'request' => 'Chi co the xac nhan de nghi khi PDT da duyet.',
                ]);
            }

            if ((int) $lockedRequest->assigned_supporting_department_id !== (int) $actor->department_id) {
                throw ValidationException::withMessages([
                    'request' => 'Nguoi dung khong thuoc khoa ho tro duoc giao.',
                ]);
            }

            $items = TeachingSupportRequestItem::query()
                ->with(['request', 'scheduleSlot.scheduleSlotGroup', 'assignedTeacher', 'scheduleSlot.teacher', 'scheduleSlot.subjectModel.department'])
                ->where('request_id', $lockedRequest->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'request' => 'Khong co slot nao trong de nghi ho tro nay.',
                ]);
            }

            $assignmentMap = collect($assignments)
                ->filter(fn (array $row): bool => isset($row['request_item_id']) && isset($row['teacher_id']) && is_numeric($row['teacher_id']))
                ->mapWithKeys(static fn (array $row): array => [
                    (int) $row['request_item_id'] => [
                        'teacher_id' => (int) $row['teacher_id'],
                        'note' => isset($row['note']) && is_string($row['note']) ? trim($row['note']) : null,
                    ],
                ]);

            if ($assignmentMap->isEmpty()) {
                throw ValidationException::withMessages([
                    'assignments' => 'Vui long chon giang vien cho cac tiet ho tro.',
                ]);
            }

            $teacherIds = $assignmentMap->pluck('teacher_id')->unique()->values()->all();
            $teachers = Teacher::query()
                ->with('department')
                ->whereIn('id', $teacherIds)
                ->get()
                ->keyBy('id');

            $departmentId = (int) $lockedRequest->assigned_supporting_department_id;
            foreach ($teacherIds as $teacherId) {
                $teacher = $teachers->get($teacherId);
                if (! $teacher instanceof Teacher || (int) $teacher->department_id !== $departmentId) {
                    throw ValidationException::withMessages([
                        'assignments' => 'Chi duoc chon giang vien thuoc khoa ho tro duoc giao.',
                    ]);
                }
            }

            $selectedItems = $items->only($assignmentMap->keys()->all());
            if ($selectedItems->count() !== $assignmentMap->count()) {
                throw ValidationException::withMessages([
                    'assignments' => 'Co slot ho tro khong ton tai trong de nghi nay.',
                ]);
            }

            $assignmentMapBySlotId = $selectedItems->mapWithKeys(function (TeachingSupportRequestItem $item) use ($assignmentMap): array {
                return [$item->schedule_slot_id => $assignmentMap->get((int) $item->id)];
            });

            $selectedSlots = $selectedItems->map->scheduleSlot->filter();
            $this->ensureAllMergedGroupsUseSameTeacher($selectedSlots, $assignmentMapBySlotId);
            $this->teacherAvailabilityService->assertAssignmentsAreAvailable(
                $selectedItems->values()->map(function (TeachingSupportRequestItem $item, int $index) use ($assignmentMap): array {
                    $payload = $assignmentMap->get((int) $item->id);

                    return [
                        'error_key' => 'assignments.' . $index . '.teacher_id',
                        'slot' => $item->scheduleSlot,
                        'teacher_id' => (int) ($payload['teacher_id'] ?? 0),
                    ];
                })->all()
            );

            $now = now();
            foreach ($selectedItems as $item) {
                $payload = $assignmentMap->get((int) $item->id);
                if ($payload === null) {
                    continue;
                }

                $oldItemValues = $this->auditService->snapshotRequestItem($item);
                $oldSlotValues = $this->auditService->snapshotSlot($item->scheduleSlot);

                $item->fill([
                    'status' => TeachingSupportRequestItem::STATUS_CONFIRMED,
                    'assigned_teacher_id' => $payload['teacher_id'],
                    'assigned_by' => $actor->id,
                    'assigned_at' => $now,
                    'note' => $payload['note'],
                ]);
                $item->save();

                $slot = $item->scheduleSlot()->lockForUpdate()->first();
                if (! $slot instanceof ScheduleSlot) {
                    throw ValidationException::withMessages([
                        'assignments' => 'Tiet hoc khong ton tai khi xac nhan ho tro.',
                    ]);
                }

                if ($slot->assignment_source !== 'internal' && $slot->assignment_source !== 'department_support') {
                    throw ValidationException::withMessages([
                        'assignments' => 'Tiet hoc da bi khoa boi mot workflow khac.',
                    ]);
                }

                $slot->fill([
                    'teacher_id' => $payload['teacher_id'],
                    'assignment_source' => 'department_support',
                    'teaching_support_request_item_id' => $item->id,
                ]);
                $slot->save();

                $this->auditService->record([
                    'request_id' => $lockedRequest->id,
                    'request_item_id' => $item->id,
                    'schedule_slot_id' => $slot->id,
                    'action' => 'support_request_item_confirmed',
                    'actor' => $actor,
                    'old_values' => [
                        'item' => $oldItemValues,
                        'slot' => $oldSlotValues,
                    ],
                    'new_values' => [
                        'item' => $this->auditService->snapshotRequestItem($item),
                        'slot' => $this->auditService->snapshotSlot($slot),
                    ],
                    'note' => $payload['note'],
                    'occurred_at' => $now,
                ]);
            }

            $oldRequestValues = $this->auditService->snapshotSupportRequest($lockedRequest);
            $lockedRequest->fill([
                'status' => TeachingSupportRequest::STATUS_COMPLETED,
            ]);
            $lockedRequest->save();

            $this->auditService->record([
                'request_id' => $lockedRequest->id,
                'action' => 'support_request_completed',
                'actor' => $actor,
                'old_values' => $oldRequestValues,
                'new_values' => $this->auditService->snapshotSupportRequest($lockedRequest),
                'note' => 'support request completed',
                'occurred_at' => $now,
            ]);

            app(InternalNotificationService::class)->notifyTeachingSupportRequestCompleted($lockedRequest, $actor);

            return $lockedRequest->fresh([
                'assignmentBatch',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.room',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher',
            ]);
        });
    }

    public function assignSupportItem(TeachingSupportRequestItem $item, User $actor, int $teacherId, ?string $note): TeachingSupportRequest
    {
        return DB::transaction(function () use ($item, $actor, $teacherId, $note): TeachingSupportRequest {
            $lockedItem = TeachingSupportRequestItem::query()
                ->with([
                    'request.requestingDepartment',
                    'request.proposedSupportingDepartment',
                    'request.assignedSupportingDepartment',
                    'request.assignmentBatch',
                    'scheduleSlot.scheduleSlotGroup',
                    'scheduleSlot.trainingClass',
                    'scheduleSlot.teacher',
                    'scheduleSlot.subjectModel.department',
                    'scheduleSlot.room',
                    'assignedTeacher',
                ])
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $request = $lockedItem->request;
            if (! $request instanceof TeachingSupportRequest) {
                throw ValidationException::withMessages([
                    'request' => 'Khong tim thay de nghi ho tro.',
                ]);
            }

            if (
                $request->status !== TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT
                && $request->status !== TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING
            ) {
                throw ValidationException::withMessages([
                    'request' => 'Chi co the phan cong khi PDT da duyet.',
                ]);
            }

            if ((int) $request->assigned_supporting_department_id !== (int) $actor->department_id) {
                throw ValidationException::withMessages([
                    'request' => 'Nguoi dung khong thuoc khoa ho tro duoc giao.',
                ]);
            }

            $teacher = Teacher::query()
                ->with('department')
                ->whereKey($teacherId)
                ->first();

            if (! $teacher instanceof Teacher || (int) $teacher->department_id !== (int) $actor->department_id) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'Chi duoc chon giang vien thuoc khoa ho tro duoc giao.',
                ]);
            }

            $slot = $lockedItem->scheduleSlot()->lockForUpdate()->first();
            if (! $slot instanceof ScheduleSlot) {
                throw ValidationException::withMessages([
                    'request_item' => 'Tiet hoc khong ton tai khi phan cong.',
                ]);
            }

            $targetItems = TeachingSupportRequestItem::query()
                ->with([
                    'scheduleSlot.scheduleSlotGroup',
                    'scheduleSlot.trainingClass',
                    'scheduleSlot.teacher',
                    'scheduleSlot.subjectModel.department',
                    'scheduleSlot.room',
                    'assignedTeacher',
                ])
                ->where('request_id', $request->id)
                ->where(function ($query) use ($slot, $lockedItem): void {
                    $query->whereKey($lockedItem->id);
                    if ($slot->scheduleSlotGroup?->status === 'active' && is_numeric($slot->schedule_slot_group_id)) {
                        $query->orWhereHas('scheduleSlot', function ($nested) use ($slot): void {
                            $nested->where('schedule_slot_group_id', (int) $slot->schedule_slot_group_id)
                                ->whereHas('scheduleSlotGroup', function ($groupQuery): void {
                                    $groupQuery->where('status', 'active');
                                });
                        });
                    }
                })
                ->lockForUpdate()
                ->get()
                ->sortBy('id')
                ->values();

            $this->teacherAvailabilityService->assertAssignmentsAreAvailable(
                $targetItems->values()->map(function (TeachingSupportRequestItem $targetItem) use ($teacherId): array {
                    return [
                        'error_key' => 'teacher_id',
                        'slot' => $targetItem->scheduleSlot,
                        'teacher_id' => $teacherId,
                    ];
                })->all()
            );

            $now = now();
            foreach ($targetItems as $targetItem) {
                $targetSlot = $targetItem->scheduleSlot()->lockForUpdate()->first();
                if (! $targetSlot instanceof ScheduleSlot) {
                    throw ValidationException::withMessages([
                        'request_item' => 'Tiet hoc khong ton tai khi phan cong.',
                    ]);
                }

                $oldItemValues = $this->auditService->snapshotRequestItem($targetItem);
                $oldSlotValues = $this->auditService->snapshotSlot($targetSlot);

                $targetItem->fill([
                    'status' => TeachingSupportRequestItem::STATUS_CONFIRMED,
                    'assigned_teacher_id' => $teacherId,
                    'assigned_by' => $actor->id,
                    'assigned_at' => $now,
                    'note' => $note,
                ]);
                $targetItem->save();

                $targetSlot->fill([
                    'teacher_id' => $teacherId,
                    'assignment_source' => 'department_support',
                    'teaching_support_request_item_id' => $targetItem->id,
                ]);
                $targetSlot->save();

                $this->auditService->record([
                    'request_id' => $request->id,
                    'request_item_id' => $targetItem->id,
                    'schedule_slot_id' => $targetSlot->id,
                    'action' => 'support_request_item_inline_assigned',
                    'actor' => $actor,
                    'old_values' => [
                        'item' => $oldItemValues,
                        'slot' => $oldSlotValues,
                    ],
                    'new_values' => [
                        'item' => $this->auditService->snapshotRequestItem($targetItem),
                        'slot' => $this->auditService->snapshotSlot($targetSlot),
                    ],
                    'note' => $note,
                    'occurred_at' => $now,
                ]);
            }

            $remainingPendingCount = TeachingSupportRequestItem::query()
                ->where('request_id', $request->id)
                ->where('status', TeachingSupportRequestItem::STATUS_PENDING)
                ->count();

            $oldRequestValues = $this->auditService->snapshotSupportRequest($request);
            $request->fill([
                'status' => $remainingPendingCount > 0
                    ? TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING
                    : TeachingSupportRequest::STATUS_COMPLETED,
            ]);
            $request->save();

            $this->auditService->record([
                'request_id' => $request->id,
                'action' => 'support_request_inline_assignment_completed',
                'actor' => $actor,
                'old_values' => $oldRequestValues,
                'new_values' => $this->auditService->snapshotSupportRequest($request),
                'note' => $note,
                'occurred_at' => $now,
            ]);

            app(InternalNotificationService::class)->notifyTeachingSupportRequestCompleted($request, $actor);

            return $request->fresh([
                'assignmentBatch',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.room',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher',
            ]);
        });
    }

    public function withdrawRequest(TeachingSupportRequest $request, User $actor, string $reason): TeachingSupportRequest
    {
        return DB::transaction(function () use ($request, $actor, $reason): TeachingSupportRequest {
            $lockedRequest = TeachingSupportRequest::query()
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
                ])
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== TeachingSupportRequest::STATUS_PENDING_PDT) {
                throw ValidationException::withMessages([
                    'request' => 'Chi co the thu hoi khi yeu cau dang cho PDT.',
                ]);
            }

            if ((int) $lockedRequest->requesting_department_id !== (int) $actor->department_id || ! $actor->isDepartmentStaff()) {
                throw ValidationException::withMessages([
                    'request' => 'Nguoi dung khong thuoc khoa yeu cau.',
                ]);
            }

            $oldRequestValues = $this->auditService->snapshotSupportRequest($lockedRequest);
            $now = now();

            foreach ($lockedRequest->items()->lockForUpdate()->get() as $item) {
                $oldItemValues = $this->auditService->snapshotRequestItem($item);
                $slot = $item->scheduleSlot()->lockForUpdate()->first();

                $item->fill([
                    'status' => TeachingSupportRequestItem::STATUS_REJECTED,
                    'assigned_teacher_id' => null,
                    'assigned_by' => $actor->id,
                    'assigned_at' => $now,
                    'note' => $reason,
                ]);
                $item->save();

                $this->auditService->record([
                    'request_id' => $lockedRequest->id,
                    'request_item_id' => $item->id,
                    'schedule_slot_id' => $slot?->id,
                    'action' => 'support_request_item_withdrawn',
                    'actor' => $actor,
                    'old_values' => [
                        'item' => $oldItemValues,
                        'slot' => $this->auditService->snapshotSlot($slot),
                    ],
                    'new_values' => [
                        'item' => $this->auditService->snapshotRequestItem($item),
                        'slot' => $this->auditService->snapshotSlot($slot),
                    ],
                    'note' => $reason,
                    'occurred_at' => $now,
                ]);
            }

            $lockedRequest->status = TeachingSupportRequest::STATUS_CANCELLED;
            $lockedRequest->pdt_note = $reason;
            $lockedRequest->save();

            $this->auditService->record([
                'request_id' => $lockedRequest->id,
                'action' => 'support_request_withdrawn',
                'actor' => $actor,
                'old_values' => $oldRequestValues,
                'new_values' => $this->auditService->snapshotSupportRequest($lockedRequest),
                'note' => $reason,
                'occurred_at' => $now,
            ]);

            app(InternalNotificationService::class)->notifyTeachingSupportRequestWithdrawn($lockedRequest, $actor);

            return $lockedRequest->fresh([
                'assignmentBatch',
                'requestingDepartment',
                'proposedSupportingDepartment',
                'assignedSupportingDepartment',
                'submittedBy',
                'pdtProcessedBy',
                'items.scheduleSlot.trainingClass',
                'items.scheduleSlot.subjectModel.department',
                'items.scheduleSlot.teacher',
                'items.scheduleSlot.room',
                'items.scheduleSlot.scheduleSlotGroup',
                'items.assignedTeacher',
            ]);
        });
    }

    /**
     * @param array<int, int> $slotIds
     * @return EloquentCollection<int, ScheduleSlot>
     */
    private function loadRequestableSlots(array $monthlyScheduleIds, array $departmentSubjectIds, array $slotIds): EloquentCollection
    {
        return ScheduleSlot::query()
            ->with([
                'monthlySchedule.plan',
                'trainingClass',
                'teacher',
                'subjectModel.department',
                'subjectLesson',
                'room',
                'scheduleSlotGroup',
            ])
            ->whereIn('monthly_schedule_id', $monthlyScheduleIds)
            ->where('slot_type', 'subject')
            ->whereNull('teacher_id')
            ->where('assignment_source', 'internal')
            ->whereNotNull('subject_lesson_id')
            ->when(
                $departmentSubjectIds !== [],
                fn ($query) => $query->whereIn('subject_id', $departmentSubjectIds)
            )
            ->whereIn('id', $slotIds)
            ->get();
    }

    /**
     * @param EloquentCollection<int, ScheduleSlot> $slots
     * @return EloquentCollection<int, ScheduleSlot>
     */
    private function expandActiveMergeGroups(EloquentCollection $slots, array $monthlyScheduleIds, array $departmentSubjectIds): EloquentCollection
    {
        $activeGroupIds = $slots
            ->filter(fn (ScheduleSlot $slot) => $slot->scheduleSlotGroup?->status === 'active')
            ->pluck('schedule_slot_group_id')
            ->filter(fn ($groupId) => is_numeric($groupId))
            ->map(fn ($groupId) => (int) $groupId)
            ->unique()
            ->values()
            ->all();

        if ($activeGroupIds === []) {
            return $slots->values();
        }

        $groupSlots = ScheduleSlot::query()
            ->with([
                'monthlySchedule.plan',
                'trainingClass',
                'teacher',
                'subjectModel.department',
                'subjectLesson',
                'room',
                'scheduleSlotGroup',
            ])
            ->whereIn('monthly_schedule_id', $monthlyScheduleIds)
            ->where('slot_type', 'subject')
            ->whereIn('schedule_slot_group_id', $activeGroupIds)
            ->whereNotNull('subject_lesson_id')
            ->when(
                $departmentSubjectIds !== [],
                fn ($query) => $query->whereIn('subject_id', $departmentSubjectIds)
            )
            ->get();

        return $slots
            ->merge($groupSlots)
            ->unique('id')
            ->values();
    }

    /**
     * @param EloquentCollection<int, ScheduleSlot> $slots
     */
    private function ensureNoDuplicateActiveRequest(EloquentCollection $slots): void
    {
        $slotIds = $slots->pluck('id')->map(static fn ($id) => (int) $id)->all();
        if ($slotIds === []) {
            throw ValidationException::withMessages([
                'slot_ids' => 'Khong co tiet hop le de tao de nghi ho tro.',
            ]);
        }

        $lockedItems = TeachingSupportRequestItem::query()
            ->with('request')
            ->whereIn('schedule_slot_id', $slotIds)
            ->whereHas('request', function ($query): void {
                $query->whereIn('status', self::ACTIVE_REQUEST_STATUSES);
            })
            ->get();

        if ($lockedItems->isEmpty()) {
            return;
        }

        $firstLocked = $lockedItems->first();
        throw ValidationException::withMessages([
            'slot_ids' => sprintf(
                'Tiet hoc #%d da nam trong de nghi ho tro dang xu ly.',
                (int) $firstLocked->schedule_slot_id
            ),
        ]);
    }

    /**
     * @param EloquentCollection<int, ScheduleSlot> $slots
     */
    private function ensureSlotsHaveClearSubjectLessons(EloquentCollection $slots): void
    {
        $invalidSlot = $slots->first(function (ScheduleSlot $slot): bool {
            if ($slot->subject_lesson_id === null) {
                return true;
            }

            return trim((string) ($slot->subjectLesson?->title ?? '')) === '';
        });

        if ($invalidSlot instanceof ScheduleSlot) {
            throw ValidationException::withMessages([
                'slot_ids' => sprintf(
                    'Tiet hoc #%d chua co bai hoc ro rang nen khong the tao de nghi ho tro.',
                    (int) $invalidSlot->id
                ),
            ]);
        }
    }

    /**
     * @param EloquentCollection<int, ScheduleSlot> $slots
     */
    private function ensureSlotsHaveRooms(EloquentCollection $slots): void
    {
        $invalidSlot = $slots->first(function (ScheduleSlot $slot): bool {
            return $slot->room_id === null;
        });

        if ($invalidSlot instanceof ScheduleSlot) {
            throw ValidationException::withMessages([
                'slot_ids' => sprintf(
                    'Tiet hoc #%d chua co phong hoc nen khong the tao de nghi ho tro.',
                    (int) $invalidSlot->id
                ),
            ]);
        }
    }

    /**
     * @param EloquentCollection<int, ScheduleSlot> $selectedSlots
     * @param Collection<int, array{teacher_id:int,note:?string}> $assignmentMap
     */
    private function ensureAllMergedGroupsUseSameTeacher(EloquentCollection $selectedSlots, Collection $assignmentMap): void
    {
        $grouped = $selectedSlots
            ->filter(fn (ScheduleSlot $slot) => $slot->scheduleSlotGroup?->status === 'active')
            ->groupBy(fn (ScheduleSlot $slot): string => (string) ($slot->schedule_slot_group_id ?? $slot->id));

        foreach ($grouped as $groupId => $groupSlots) {
            $teacherIds = $groupSlots
                ->map(fn (ScheduleSlot $slot): ?int => $assignmentMap->get((int) $slot->id)['teacher_id'] ?? null)
                ->filter(fn ($teacherId) => is_numeric($teacherId))
                ->map(fn ($teacherId) => (int) $teacherId)
                ->unique();

            if ($teacherIds->count() <= 1) {
                continue;
            }

            throw ValidationException::withMessages([
                'assignments' => sprintf(
                    'Nhom ghep active #%s phai duoc ho tro boi cung 1 giang vien.',
                    $groupId
                ),
            ]);
        }
    }

    private function normalizeSlotIds(array $slotIds): array
    {
        return collect($slotIds)
            ->filter(fn ($slotId) => is_numeric($slotId))
            ->map(fn ($slotId) => (int) $slotId)
            ->filter(fn (int $slotId) => $slotId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveAssignmentBatch(MonthlySchedule $monthlySchedule, int $departmentId): DepartmentMonthlyAssignmentBatch
    {
        $batch = DepartmentMonthlyAssignmentBatch::query()
            ->where('department_id', $departmentId)
            ->where('month', (int) $monthlySchedule->month)
            ->where('year', (int) $monthlySchedule->year)
            ->lockForUpdate()
            ->first();

        if ($batch) {
            return $batch;
        }

        return DepartmentMonthlyAssignmentBatch::query()->create([
            'department_id' => $departmentId,
            'month' => (int) $monthlySchedule->month,
            'year' => (int) $monthlySchedule->year,
            'status' => 'draft',
            'version' => 1,
        ]);
    }

    private function canCreateRequestFromScope(array $scope): bool
    {
        return isset($scope['department_id']);
    }

    private function isSameActiveMergeGroup(ScheduleSlot $first, ScheduleSlot $second): bool
    {
        $firstGroupId = $this->getActiveScheduleSlotGroupId($first);
        $secondGroupId = $this->getActiveScheduleSlotGroupId($second);

        if ($firstGroupId === null || $secondGroupId === null) {
            return false;
        }

        return $firstGroupId === $secondGroupId;
    }

    private function getActiveScheduleSlotGroupId(ScheduleSlot $slot): ?int
    {
        if ($slot->scheduleSlotGroup?->status !== 'active') {
            return null;
        }

        return is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null;
    }

    private function labelForRequestStatus(?string $status): string
    {
        return match ($status) {
            TeachingSupportRequest::STATUS_PENDING_PDT => 'Chờ PĐT',
            TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT => 'PĐT đã duyệt',
            TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING => 'Khoa đang phân công',
            TeachingSupportRequest::STATUS_COMPLETED => 'Hoàn tất',
            TeachingSupportRequest::STATUS_RETURNED => 'Đã từ chối',
            TeachingSupportRequest::STATUS_CANCELLED => 'Đã huỷ',
            default => $status ? strtoupper($status) : '-',
        };
    }

    private function labelForItemStatus(?string $status): string
    {
        return match ($status) {
            TeachingSupportRequestItem::STATUS_PENDING => 'Chờ phân công',
            TeachingSupportRequestItem::STATUS_ASSIGNED => 'Đã giao GV',
            TeachingSupportRequestItem::STATUS_CONFIRMED => 'Đã xác nhận',
            TeachingSupportRequestItem::STATUS_REJECTED => 'Từ chối',
            default => $status ? strtoupper($status) : '-',
        };
    }

    /**
     * @param ScheduleSlot $slot
     * @return array<string, mixed>
     */
    private function formatSupportMeta(ScheduleSlot $slot, ?TeachingSupportRequestItem $item, TeachingSupportRequest $request): array
    {
        $status = $request->status;
        $supportDepartmentName = $request->assignedSupportingDepartment?->name
            ?? $request->proposedSupportingDepartment?->name
            ?? '-';

        $labelMap = [
            TeachingSupportRequest::STATUS_PENDING_PDT => 'Chờ PĐT',
            TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT => 'PĐT đã duyệt',
            TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING => 'Khoa đang phân công',
            TeachingSupportRequest::STATUS_COMPLETED => 'GV hỗ trợ',
            TeachingSupportRequest::STATUS_RETURNED => 'Đã từ chối',
            TeachingSupportRequest::STATUS_CANCELLED => 'Đã huỷ',
        ];

        return [
            'request_id' => $request->id,
            'request_item_id' => $item?->id,
            'status' => $status,
            'label' => $labelMap[$status] ?? strtoupper($status),
            'support_department_name' => $supportDepartmentName,
            'teacher_id' => $slot->teacher_id,
            'teacher_name' => $slot->teacher?->name ?? $item?->assignedTeacher?->name,
            'locked' => in_array($status, [
                TeachingSupportRequest::STATUS_PENDING_PDT,
                TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                TeachingSupportRequest::STATUS_COMPLETED,
                TeachingSupportRequest::STATUS_CANCELLED,
            ], true),
        ];
    }
}
