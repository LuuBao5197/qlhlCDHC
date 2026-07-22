<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Http\Controllers\Controller;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleRequest;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleViewRequest;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Application\TeachingSupportRequest\TeachingSupportRequestService;
use Modules\Training\Models\Room;
use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Teacher;

class AssignMonthlyScheduleController extends Controller
{
    public function __construct(
        private AssignMonthlyScheduleHandler $handler,
        private MonthlyAssignmentScopeResolver $scopeResolver,
        private TeachingSupportRequestService $teachingSupportRequestService
    ) {}

    /**
     * Show assignment form for one monthly schedule.
     */

    public function showForm(AssignMonthlyScheduleViewRequest $request, int $id)
    {
        $user = $request->user();
        $monthlySchedule = MonthlySchedule::query()
            ->with([
                'plan',
                'trainingClass.department',
            ])
            ->findOrFail($id);

        $scope = $this->scopeResolver->resolve($monthlySchedule, $user);
        if ($scope === null) {
            abort(422, 'Khong xac dinh duoc khoa hien tai de tong hop phan cong.');
        }

        $departmentId = $scope['department_id'];
        $departmentName = $scope['department_name'];
        $aggregateMonthlySchedules = $scope['monthly_schedules'];
        $aggregateMonthlyScheduleIds = $scope['monthly_schedule_ids'];
        $aggregatePlanCount = $scope['plan_count'];
        $aggregateMonthlyScheduleCount = $scope['monthly_schedule_count'];
        $departmentSubjectIds = $scope['department_subject_ids'];
        $currentBatch = DepartmentMonthlyAssignmentBatch::query()
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
            ->where('department_id', $departmentId)
            ->where('month', $monthlySchedule->month)
            ->where('year', $monthlySchedule->year)
            ->orderByRaw("CASE status WHEN 'approved' THEN 4 WHEN 'submitted' THEN 3 WHEN 'draft' THEN 2 WHEN 'returned' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        $aggregateSubjectSlots = ScheduleSlot::query()
            ->with([
                'monthlySchedule.plan',
                'monthlySchedule.trainingClass.department',
                'trainingClass',
                'teacher',
                'subjectModel.department',
                'subjectLesson',
                'room',
                'scheduleSlotGroup',
            ])
            ->whereIn('monthly_schedule_id', $aggregateMonthlyScheduleIds)
            ->where('slot_type', 'subject')
            ->when(
                $departmentSubjectIds !== [],
                fn ($query) => $query->whereIn('subject_id', $departmentSubjectIds)
            )
            ->orderBy('date')
            ->orderBy('period_number')
            ->orderBy('monthly_schedule_id')
            ->orderBy('class_id')
            ->orderBy('id')
            ->get();

        $aggregateEventSlotCount = ScheduleSlot::query()
            ->whereIn('monthly_schedule_id', $aggregateMonthlyScheduleIds)
            ->where('slot_type', 'event')
            ->count();

        $teachers = Teacher::query()
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();

        $supportMeta = $this->teachingSupportRequestService->buildMonthlyAssignmentSupportMeta($monthlySchedule, $user);
        $supportWorkload = $this->teachingSupportRequestService->buildDepartmentSupportWorkload($monthlySchedule, $user);

        $subjects = Subject::with('department')
            ->where('department_id', $departmentId)
            ->orderBy('code')
            ->get();

        $subjectLessons = SubjectLesson::query()
            ->when(
                $subjects->isNotEmpty(),
                fn($query) => $query->whereIn('subject_id', $subjects->pluck('id'))
            )
            ->orderBy('subject_id')
            ->orderBy('lesson_no')
            ->get();

        $rooms = Room::query()->orderBy('code')->get();
        $supportDepartments = Department::query()
            ->where('id', '!=', (int) $departmentId)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('schedule::monthly-assignment', [
            'monthlySchedule' => $monthlySchedule,
            'aggregateMonthlySchedules' => $aggregateMonthlySchedules,
            'aggregatePlanCount' => $aggregatePlanCount,
            'aggregateMonthlyScheduleCount' => $aggregateMonthlyScheduleCount,
            'aggregateSlots' => $aggregateSubjectSlots,
            'aggregateEventSlotCount' => $aggregateEventSlotCount,
            'departmentName' => $departmentName,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'subjectLessons' => $subjectLessons,
            'rooms' => $rooms,
            'supportDepartments' => $supportDepartments,
            'supportSlotMeta' => $supportMeta['slot_meta'] ?? [],
            'supportRequestSummary' => $supportMeta['summary'] ?? [],
            'canCreateSupportRequest' => (bool) ($supportMeta['can_create_request'] ?? false),
            'supportWorkloadRows' => $supportWorkload['rows'] ?? collect(),
            'supportTeacherAvailabilityMap' => $supportWorkload['teacher_availability_map'] ?? [],
            'supportWorkloadSummary' => $supportWorkload['summary'] ?? [],
            'currentDepartmentId' => (int) $departmentId,
            'currentBatch' => $currentBatch,
            'canSubmitToTrainingOffice' => false,
            'isAggregateAssignment' => true,
        ]);
    }

    /**
     * Save monthly assignment updates.
     */
    public function __invoke(AssignMonthlyScheduleRequest $request, int $id)
    {
        return $this->handler->handle($request, $id);
    }
}
