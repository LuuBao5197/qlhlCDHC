<?php

namespace Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Training\Models\Department;

class DepartmentMonthlyAssignmentBatchController extends Controller
{
    public function __construct(
        private SubmitDepartmentMonthlyAssignmentBatch $submitBatch
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorizeQueue($user);

        $filters = $this->resolveIndexFilters($request);

        // Lanh dao Khoa / nhan vien Khoa chi duoc xem batch cua dung khoa minh.
        if ($user && ! $user->isAdmin() && ! $user->isTrainingOffice() && $user->isDepartmentStaff()) {
            $filters['department_id'] = (int) $user->department_id;
        }

        $query = DepartmentMonthlyAssignmentBatch::query()
            ->with([
                'department',
                'submittedBy',
                'departmentReviewedBy',
                'trainingOfficeReviewedBy',
                'batchSlots.scheduleSlot.monthlySchedule.plan',
                'batchSlots.scheduleSlot.scheduleSlotGroup',
            ])
            ->withCount('batchSlots');

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['department_id'] !== null) {
            $query->where('department_id', $filters['department_id']);
        }

        if ($filters['month'] !== null) {
            $query->where('month', $filters['month']);
        }

        if ($filters['year'] !== null) {
            $query->where('year', $filters['year']);
        }

        $batches = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $batches->setCollection(
            $batches->getCollection()->map(fn (DepartmentMonthlyAssignmentBatch $batch): array => $this->formatIndexBatch($batch))
        );

        return response()->view('schedule::department-monthly-assignment-batch.index', [
            'batches' => $batches,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'statusTabs' => [
                'submitted' => 'Chờ duyệt',
                'approved' => 'Đã duyệt (đủ 2 vòng)',
                'returned' => 'Trả về chỉnh sửa',
                'all' => 'Tất cả',
            ],
        ]);
    }

    public function submit(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $anchorMonthlySchedule = MonthlySchedule::query()
            ->with(['scheduleSlots.subjectModel.department', 'plan'])
            ->findOrFail($id);

        $this->authorizeDepartmentScope($request->user(), $anchorMonthlySchedule);

        try {
            $batch = $this->submitBatch->handle($anchorMonthlySchedule, $request->user());
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
                ->route('monthly-schedule.assignment', $id)
                ->with('success', 'Da gui batch len Lanh dao Khoa de duyet.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Da gui batch len Lanh dao Khoa de duyet.',
            'batch_id' => $batch->id,
            'batch' => $this->formatBatch($batch),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse|Response
    {
        $batch = DepartmentMonthlyAssignmentBatch::query()
            ->with([
                'department',
                'submittedBy',
                'departmentReviewedBy',
                'trainingOfficeReviewedBy',
                'batchSlots.scheduleSlot.monthlySchedule.plan',
                'batchSlots.scheduleSlot.trainingClass',
                'batchSlots.scheduleSlot.teacher',
                'batchSlots.scheduleSlot.subjectModel.department',
                'batchSlots.scheduleSlot.subjectLesson',
                'batchSlots.scheduleSlot.room',
                'batchSlots.scheduleSlot.scheduleSlotGroup',
            ])
            ->findOrFail($id);

        $this->authorizeBatchView($request->user(), $batch);

        if (! $request->expectsJson()) {
            return response()->view('schedule::department-monthly-assignment-batch.show', [
                'batch' => $batch,
                'batchData' => $this->formatBatch($batch),
                'canReviewAsDepartmentLeadership' => (bool) $request->user()?->can('reviewAsDepartmentLeadership', $batch),
                'canReviewAsTrainingOffice' => (bool) $request->user()?->can('reviewAsTrainingOffice', DepartmentMonthlyAssignmentBatch::class),
                'anchorMonthlyScheduleId' => $batch->batchSlots->first()?->scheduleSlot?->monthly_schedule_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'batch' => $this->formatBatch($batch),
        ]);
    }

    private function authorizeDepartmentScope(?User $user, MonthlySchedule $monthlySchedule): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return;
        }

        if (! $user->isDepartmentStaff()) {
            abort(403);
        }

        $scope = app(ResolveAggregateAssignmentScope::class)->handle($monthlySchedule, $user);
        $departmentId = $scope['department_id'] ?? null;

        if ($departmentId === null || ! $user->can('submit', [DepartmentMonthlyAssignmentBatch::class, (int) $departmentId])) {
            abort(403);
        }
    }

    private function authorizeBatchView(?User $user, DepartmentMonthlyAssignmentBatch $batch): void
    {
        if (! $user || ! $user->can('view', $batch)) {
            abort(403);
        }
    }

    private function authorizeQueue(?User $user): void
    {
        if (! $user || ! $user->can('viewQueue', DepartmentMonthlyAssignmentBatch::class)) {
            abort(403);
        }
    }

    /**
     * @return array{
     *     status:string,
     *     department_id:int|null,
     *     month:int|null,
     *     year:int|null
     * }
     */
    private function resolveIndexFilters(Request $request): array
    {
        $status = (string) $request->query('status', 'submitted');
        $allowedStatuses = ['submitted', 'approved', 'returned', 'all'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'submitted';
        }

        $departmentId = $request->integer('department_id');
        $departmentId = $departmentId > 0 ? $departmentId : null;

        $month = $request->integer('month');
        $month = $month >= 1 && $month <= 12 ? $month : null;

        $year = $request->integer('year');
        $year = $year > 0 ? $year : null;

        if ($month === null) {
            $month = (int) now()->month;
        }

        if ($year === null) {
            $year = (int) now()->year;
        }

        return [
            'status' => $status,
            'department_id' => $departmentId,
            'month' => $month,
            'year' => $year,
        ];
    }

    /**
     * @return array{
     *     id:int,
     *     department_id:int,
     *     department_name:string|null,
     *     month:int,
     *     year:int,
     *     status:string,
     *     submitted_by:int|null,
     *     submitted_by_name:string|null,
     *     submitted_at:string|null,
     *     reviewed_by:int|null,
     *     reviewed_by_name:string|null,
     *     reviewed_at:string|null,
     *     processing_time:string|null,
     *     slot_count:int,
     *     source_plan_count:int,
     *     source_monthly_schedule_count:int,
     *     assigned_slot_count:int,
     *     unassigned_slot_count:int,
     *     active_merge_group_count:int
     * }
     */
    private function formatIndexBatch(DepartmentMonthlyAssignmentBatch $batch): array
    {
        $batch->loadMissing([
            'department',
            'submittedBy',
            'departmentReviewedBy',
            'trainingOfficeReviewedBy',
            'batchSlots.scheduleSlot.monthlySchedule.plan',
            'batchSlots.scheduleSlot.scheduleSlotGroup',
        ]);

        $slots = $batch->batchSlots->map->scheduleSlot->filter();
        $slotCount = (int) $batch->batch_slots_count;
        $assignedSlotCount = $slots->filter(fn ($slot) => ! $this->isUnassignedSlot($slot))->count();
        $sourceMonthlyScheduleCount = $slots->pluck('monthly_schedule_id')->filter()->unique()->count();
        $sourcePlanCount = $slots->map(fn ($slot) => $slot?->monthlySchedule?->plan_id)->filter()->unique()->count();
        $activeMergeGroupCount = $slots
            ->filter(fn ($slot) => ($slot?->scheduleSlotGroup?->status ?? null) === 'active')
            ->pluck('schedule_slot_group_id')
            ->filter()
            ->unique()
            ->count();

        $lastReviewedAt = $batch->training_office_reviewed_at ?? $batch->department_reviewed_at;

        return [
            'id' => (int) $batch->id,
            'department_id' => (int) $batch->department_id,
            'department_name' => $batch->department?->name,
            'month' => (int) $batch->month,
            'year' => (int) $batch->year,
            'status' => (string) $batch->status,
            'current_step' => (string) $batch->current_step,
            'submitted_by' => $batch->submitted_by !== null ? (int) $batch->submitted_by : null,
            'submitted_by_name' => $batch->submittedBy?->name,
            'submitted_at' => optional($batch->submitted_at)->format('d/m/Y H:i'),
            'department_reviewed_by_name' => $batch->departmentReviewedBy?->name,
            'department_reviewed_at' => optional($batch->department_reviewed_at)->format('d/m/Y H:i'),
            'training_office_reviewed_by_name' => $batch->trainingOfficeReviewedBy?->name,
            'training_office_reviewed_at' => optional($batch->training_office_reviewed_at)->format('d/m/Y H:i'),
            'processing_time' => $this->formatProcessingTime($batch->submitted_at, $lastReviewedAt),
            'slot_count' => $slotCount,
            'source_plan_count' => $sourcePlanCount,
            'source_monthly_schedule_count' => $sourceMonthlyScheduleCount,
            'assigned_slot_count' => $assignedSlotCount,
            'unassigned_slot_count' => max($slotCount - $assignedSlotCount, 0),
            'active_merge_group_count' => $activeMergeGroupCount,
        ];
    }

    private function formatProcessingTime($submittedAt, $reviewedAt): ?string
    {
        if (! $submittedAt || ! $reviewedAt) {
            return null;
        }

        $seconds = $submittedAt->diffInSeconds($reviewedAt);
        $days = intdiv($seconds, 86400);
        $seconds -= $days * 86400;
        $hours = intdiv($seconds, 3600);
        $seconds -= $hours * 3600;
        $minutes = intdiv($seconds, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ngày';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' giờ';
        }
        if ($minutes > 0 || $parts === []) {
            $parts[] = $minutes . ' phút';
        }

        return implode(' ', $parts);
    }

    private function formatBatch(DepartmentMonthlyAssignmentBatch $batch): array
    {
        $batch->loadMissing([
            'department',
            'submittedBy',
            'departmentReviewedBy',
            'trainingOfficeReviewedBy',
            'batchSlots.scheduleSlot.monthlySchedule.plan',
            'batchSlots.scheduleSlot.trainingClass',
            'batchSlots.scheduleSlot.teacher',
            'batchSlots.scheduleSlot.subjectModel.department',
            'batchSlots.scheduleSlot.subjectLesson',
            'batchSlots.scheduleSlot.room',
            'batchSlots.scheduleSlot.scheduleSlotGroup',
        ]);

        return [
            'id' => $batch->id,
            'department_id' => $batch->department_id,
            'department_name' => $batch->department?->name,
            'month' => $batch->month,
            'year' => $batch->year,
            'status' => $batch->status,
            'current_step' => $batch->current_step,
            'version' => $batch->version,
            'submitted_by' => $batch->submitted_by,
            'submitted_by_name' => $batch->submittedBy?->name,
            'submitted_at' => optional($batch->submitted_at)->toDateTimeString(),
            'department_reviewed_by' => $batch->department_reviewed_by,
            'department_reviewed_by_name' => $batch->departmentReviewedBy?->name,
            'department_reviewed_at' => optional($batch->department_reviewed_at)->toDateTimeString(),
            'department_review_note' => $batch->department_review_note,
            'training_office_reviewed_by' => $batch->training_office_reviewed_by,
            'training_office_reviewed_by_name' => $batch->trainingOfficeReviewedBy?->name,
            'training_office_reviewed_at' => optional($batch->training_office_reviewed_at)->toDateTimeString(),
            'training_office_review_note' => $batch->training_office_review_note,
            'slot_count' => $batch->batchSlots->count(),
            'slots' => $batch->batchSlots->map(function ($batchSlot): array {
                $slot = $batchSlot->scheduleSlot;

                return [
                    'batch_slot_id' => $batchSlot->id,
                    'schedule_slot_id' => $slot?->id,
                    'monthly_schedule_id' => $slot?->monthly_schedule_id,
                    'monthly_schedule_plan_id' => $slot?->monthlySchedule?->plan_id,
                    'monthly_schedule_plan_name' => $slot?->monthlySchedule?->plan?->name,
                    'date' => optional($slot?->date)->toDateString(),
                    'period_number' => $slot?->period_number,
                    'class_id' => $slot?->class_id,
                    'class_code' => $slot?->trainingClass?->code,
                    'class_name' => $slot?->trainingClass?->name,
                    'subject_id' => $slot?->subject_id,
                    'subject_code' => $slot?->subjectModel?->code,
                    'subject_name' => $slot?->subjectModel?->name,
                    'subject_lesson_id' => $slot?->subject_lesson_id,
                    'lesson_label' => $slot?->subjectLesson
                        ? 'B' . $slot->subjectLesson->lesson_no . ': ' . $slot->subjectLesson->title
                        : null,
                    'teacher_id' => $slot?->teacher_id,
                    'assignment_type' => $slot?->assignment_type,
                    'teacher_name' => $this->resolveTeacherName($slot),
                    'room_id' => $slot?->room_id,
                    'room_code' => $slot?->room?->code,
                    'slot_type' => $slot?->slot_type,
                    'slot_status' => $slot?->slot_status,
                    'merge_group_id' => $slot?->schedule_slot_group_id,
                    'merge_group_status' => $slot?->scheduleSlotGroup?->status,
                ];
            })->values(),
        ];
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

    private function isUnassignedSlot(?\Modules\Schedule\Models\ScheduleSlot $slot): bool
    {
        if (! $slot) {
            return true;
        }

        return blank($slot->teacher_id) && blank($slot->assignment_type);
    }

    private function resolveTeacherName(?\Modules\Schedule\Models\ScheduleSlot $slot): ?string
    {
        if (! $slot) {
            return null;
        }

        return match ($slot->assignment_type) {
            \Modules\Schedule\Models\ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY => 'Lớp tự nghiên cứu',
            default => $slot->teacher?->name,
        };
    }
}
