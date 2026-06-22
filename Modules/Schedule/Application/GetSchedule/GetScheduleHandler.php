<?php

namespace Modules\Schedule\Application\GetSchedule;

use Illuminate\Support\Facades\Schema;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\ChangeRequest;
use Modules\Training\Models\HolidayCalendar;
use Modules\Training\Models\Room;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Teacher;

class GetScheduleHandler
{
    /**
     * Handle the get schedules request.
     */
    public function handle(GetScheduleRequest $request)
    {
        // Get all schedules with pagination
        $perPage = $request->input('per_page', 15);
        $schedules = Plans::query()
            ->with(['createdBy', 'submittedBy', 'trainingBatch.trainingProgram'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $monthlySchedules = MonthlySchedule::query()
            ->with([
                'plan',
                'createdBy',
                'scheduleSlots' => static function ($query): void {
                    $query
                        ->with(['trainingClass', 'room', 'subjectModel.department', 'teacher', 'subjectLesson'])
                        ->orderBy('date')
                        ->orderBy('period_number');
                },
            ])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $changeRequests = ChangeRequest::query()
            ->with([
                'monthlySchedule.plan',
                'scheduleSlot.trainingClass',
                'scheduleSlot.teacher',
                'scheduleSlot.subjectModel',
                'scheduleSlot.subjectLesson.subject',
                'scheduleSlot.room',
                'requestedBy.department',
                'changeRequestItems.scheduleSlot.trainingClass',
                'changeRequestItems.scheduleSlot.teacher',
                'changeRequestItems.scheduleSlot.subjectModel',
                'changeRequestItems.scheduleSlot.subjectLesson.subject',
                'changeRequestItems.scheduleSlot.room',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $rooms = Room::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // $teachers = User::query()
        //     ->where('role', User::ROLE_TEACHER)
        //     ->orderBy('name')
        //     ->get(['id', 'name', 'employee_code']);

        $authUser = $request->user();
        $departmentId = $authUser?->department_id;

        $teachersQuery = Teacher::query()
            ->orderBy('name');

        if ($authUser?->isDepartmentStaff() && $departmentId) {
            $teachersQuery->where('department_id', $departmentId);
        }

        $teachers = $teachersQuery
            ->select(['id', 'name', 'teacher_code as employee_code', 'department_id'])
            ->get();

        // Unfiltered lookup for rendering labels in review tables (before/after change payload).
        $teacherLookup = Teacher::query()
            ->orderBy('name')
            ->get(['id', 'name', 'teacher_code']);

        $subjectLessons = SubjectLesson::query()
            ->with(['subject:id,code,name'])
            ->orderBy('subject_id')
            ->orderBy('lesson_no')
            ->get(['id', 'subject_id', 'lesson_no', 'title']);

        $holidayCalendars = Schema::hasTable('holiday_calendars')
            ? HolidayCalendar::query()
                ->orderByDesc('date')
                ->get(['id', 'name', 'date', 'note', 'is_active'])
            : collect();

        return view('schedule::index', [
            'schedules' => $schedules,
            'monthlySchedules' => $monthlySchedules,
            'changeRequests' => $changeRequests,
            'rooms' => $rooms,
            'teachers' => $teachers,
            'teacherLookup' => $teacherLookup,
            'subjectLessons' => $subjectLessons,
            'holidayCalendars' => $holidayCalendars,
        ]);
    }
}
