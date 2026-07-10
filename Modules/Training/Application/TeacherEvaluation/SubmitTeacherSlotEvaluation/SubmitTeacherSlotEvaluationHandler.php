<?php

namespace Modules\Training\Application\TeacherEvaluation\SubmitTeacherSlotEvaluation;

use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\SlotEvaluation;
use Modules\Training\Models\Teacher;

class SubmitTeacherSlotEvaluationHandler
{
    public function handle(SubmitTeacherSlotEvaluationRequest $request, int $evaluatorId): void
    {
        $validated = $request->validated();
        $teacher = $this->resolveTeacherFromUser($request->user()?->id, $request->user()?->employee_code, $request->user()?->name);

        if ($teacher === null) {
            return;
        }

        $slotsInput = $validated['slots'] ?? [];
        if (!is_array($slotsInput) || $slotsInput === []) {
            return;
        }

        $slotIds = collect(array_keys($slotsInput))
            ->filter(static fn ($id) => is_numeric($id))
            ->map(static fn ($id) => (int) $id)
            ->values();

        if ($slotIds->isEmpty()) {
            return;
        }

        $allowedSlots = ScheduleSlot::query()
            ->where('teacher_id', $teacher->id)
            ->whereDate('date', $validated['date'])
            ->whereIn('id', $slotIds)
            ->get()
            ->keyBy('id');

        foreach ($slotIds as $slotId) {
            $slot = $allowedSlots->get($slotId);
            if ($slot === null) {
                continue;
            }

            $slotData = $slotsInput[$slotId] ?? [];
            $attendanceCount = array_key_exists('attendance_count', $slotData) && $slotData['attendance_count'] !== ''
                ? (int) $slotData['attendance_count']
                : null;
            $absentCount = array_key_exists('absent_count', $slotData) && $slotData['absent_count'] !== ''
                ? (int) $slotData['absent_count']
                : null;
            $ratingLevel = (string) ($slotData['rating_level'] ?? '');
            $comment = array_key_exists('comment', $slotData)
                ? trim((string) $slotData['comment'])
                : null;
            $comment = $comment === '' ? null : $comment;

            if ($attendanceCount === null && $absentCount === null && $ratingLevel === '' && $comment === null) {
                continue;
            }

            SlotEvaluation::updateOrCreate(
                [
                    'schedule_slot_id' => $slot->id,
                    'evaluator_id' => $evaluatorId,
                ],
                [
                    'attendance_count' => $attendanceCount,
                    'absent_count' => $absentCount,
                    'rating_level' => $ratingLevel,
                    'comment' => $comment,
                ]
            );
        }
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
