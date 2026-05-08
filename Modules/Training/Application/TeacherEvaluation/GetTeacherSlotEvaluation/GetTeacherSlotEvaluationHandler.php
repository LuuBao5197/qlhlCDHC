<?php

namespace Modules\Training\Application\TeacherEvaluation\GetTeacherSlotEvaluation;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\SlotEvaluation;
use Modules\Training\Models\Teacher;

class GetTeacherSlotEvaluationHandler
{
    public function handle(Request $request)
    {
        $user = $request->user();

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $teacher = $this->resolveTeacherFromUser($user?->id, $user?->employee_code, $user?->name);

        $slots = collect();
        $evaluations = collect();

        if ($teacher !== null) {
            $slots = ScheduleSlot::query()
                ->with(['trainingClass', 'room', 'subjectModel', 'subjectLesson'])
                ->where('teacher_id', $teacher->id)
                ->whereDate('date', $date)
                ->orderBy('period_number')
                ->get();

            $evaluations = SlotEvaluation::query()
                ->where('evaluator_id', $user?->id)
                ->whereIn('schedule_slot_id', $slots->pluck('id'))
                ->get()
                ->keyBy('schedule_slot_id');
        }

        return view('training::teacher-evaluation.slot-evaluation', [
            'date' => Carbon::parse($date),
            'teacher' => $teacher,
            'slots' => $slots,
            'evaluations' => $evaluations,
            'authUser' => $user,
        ]);
    }

    private function resolveTeacherFromUser(?int $userId, ?string $employeeCode, ?string $name): ?Teacher
    {
        if ($userId !== null) {
            $teacher = Teacher::query()
                ->where('user_id', $userId)
                ->first();

            if ($teacher !== null) {
                return $teacher;
            }
        }

        if ($employeeCode !== null && $employeeCode !== '') {
            $teacher = Teacher::query()
                ->where('teacher_code', $employeeCode)
                ->first();

            if ($teacher !== null) {
                return $teacher;
            }
        }

        if ($name === null || trim($name) === '') {
            return null;
        }

        return Teacher::query()
            ->where('name', $name)
            ->first();
    }
}
