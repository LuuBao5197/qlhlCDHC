<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleRequest;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleViewRequest;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Training\Models\Room;
use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Teacher;

class AssignMonthlyScheduleController extends Controller
{
    public function __construct(
        private AssignMonthlyScheduleHandler $handler
    ) {}

    /**
     * Show assignment form for one monthly schedule.
     */

    public function showForm(AssignMonthlyScheduleViewRequest $request, int $id)
    {
        $user = $request->user();
        $departmentId = $user?->department_id;

        // Get subject IDs belonging to this department (for filtering slots)
        $departmentSubjectIds = [];
        if ($departmentId !== null) {
            $departmentSubjectIds = Subject::where('department_id', $departmentId)
                ->pluck('id')
                ->all();
        }

        $monthlySchedule = MonthlySchedule::query()
            ->with([
                'plan',
                'scheduleSlots' => function ($query) use ($departmentSubjectIds): void {
                    // Only show slots whose subject belongs to this department
                    if ($departmentSubjectIds !== []) {
                        $query->whereIn('subject_id', $departmentSubjectIds);
                    }
                    $query->orderBy('date')->orderBy('period_number');
                },
                'scheduleSlots.trainingClass',
            ])
            ->findOrFail($id);

        // $teachers = User::query()
        //     ->where('role', User::ROLE_TEACHER)
        //     ->when(
        //         $departmentId !== null,
        //         fn($query) => $query->where('department_id', $departmentId)
        //     )
        //     ->orderBy('name')
        //     ->get();

        $teachers = Teacher::query()->where('department_id', $departmentId)->orderBy('name')->get();

        $subjects = Subject::with('department')
            ->when(
                $departmentId !== null,
                fn($query) => $query->where('department_id', $departmentId)
            )
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

        return view('schedule::monthly-assignment', [
            'monthlySchedule' => $monthlySchedule,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'subjectLessons' => $subjectLessons,
            'rooms' => $rooms,
            'canSubmitToTrainingOffice' => $user?->isDepartmentStaff() || $user?->isAdmin(),
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
