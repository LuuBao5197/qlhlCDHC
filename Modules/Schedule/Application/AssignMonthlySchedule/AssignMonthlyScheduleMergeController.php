<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;
use Modules\Training\Models\SubjectLesson;

class AssignMonthlyScheduleMergeController extends Controller
{
    public function __construct(
        private ScheduleSlotMergeService $mergeService
    ) {}

    public function mergeCandidates(Request $request, int $id, int $slotId): JsonResponse
    {
        $monthlySchedule = MonthlySchedule::query()->find($id);
        if (! $monthlySchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Monthly schedule not found.',
            ], 404);
        }

        $slot = $this->findMonthlyScheduleSlot($monthlySchedule->id, $slotId);
        if (! $slot) {
            return response()->json([
                'success' => false,
                'message' => 'Slot not found in the selected monthly schedule.',
            ], 404);
        }

        if ($slot->assignment_type === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        if ($slot->isRegularTestLesson()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $request->user());
        if ($scope === null) {
            return response()->json([
                'success' => false,
                'message' => 'Khong xac dinh duoc khoa hien tai de tim tiet co the ghep.',
            ], 422);
        }

        $candidates = $this->mergeService->getMergeCandidates($slot, $scope['monthly_schedule_ids'])
            ->loadMissing(['trainingClass', 'subjectModel', 'subjectLesson', 'teacher', 'room']);

        return response()->json([
            'success' => true,
            'data' => $candidates->map(fn (ScheduleSlot $candidate) => $this->formatCandidate($candidate))->values(),
        ]);
    }

    public function merge(Request $request, int $id, int $slotId): JsonResponse
    {
        $monthlySchedule = MonthlySchedule::query()->find($id);
        if (! $monthlySchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Monthly schedule not found.',
            ], 404);
        }

        $baseSlot = $this->findMonthlyScheduleSlot($monthlySchedule->id, $slotId);
        if (! $baseSlot) {
            return response()->json([
                'success' => false,
                'message' => 'Base slot not found in the selected monthly schedule.',
            ], 404);
        }

        if ($baseSlot->assignment_type === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
            return response()->json([
                'success' => false,
                'message' => 'Tiet tu nghien cuu khong duoc ghep lop.',
                'errors' => [
                    'slots.0' => ['Tiet tu nghien cuu khong duoc ghep lop.'],
                ],
            ], 422);
        }

        if ($baseSlot->isRegularTestLesson()) {
            return response()->json([
                'success' => false,
                'message' => 'Bai kiem tra thuong xuyen khong duoc ghep lop.',
                'errors' => [
                    'slots.0' => ['Bai kiem tra thuong xuyen khong duoc ghep lop.'],
                ],
            ], 422);
        }

        $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $request->user());
        if ($scope === null) {
            return response()->json([
                'success' => false,
                'message' => 'Khong xac dinh duoc khoa hien tai de ghep lop.',
            ], 422);
        }

        $validated = $request->validate([
            'candidate_slot_ids' => ['required', 'array', 'min:1'],
            'candidate_slot_ids.*' => ['required', 'integer', 'distinct', 'exists:schedule_slots,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'assignment_type' => ['nullable', 'string', 'in:self_study'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'lesson_type' => ['nullable', 'string', 'in:theory,practice'],
            'content' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignmentType = $validated['assignment_type'] ?? null;
        $subjectLessonId = $validated['subject_lesson_id'] ?? null;
        $lessonType = $validated['lesson_type'] ?? null;

        if ($assignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY && $subjectLessonId !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Tiet tu nghien cuu khong duoc chon bai hoc.',
                'errors' => [
                    'subject_lesson_id' => ['Tiet tu nghien cuu khong duoc chon bai hoc.'],
                ],
            ], 422);
        }

        if ($assignmentType !== ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY && $subjectLessonId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tiet hoc phai co bai hoc.',
                'errors' => [
                    'subject_lesson_id' => ['Tiet hoc phai co bai hoc.'],
                ],
            ], 422);
        }

        $isRegularTestLesson = $subjectLessonId !== null
            && (bool) SubjectLesson::query()->whereKey($subjectLessonId)->value('is_regular_test');

        if ($isRegularTestLesson) {
            return response()->json([
                'success' => false,
                'message' => 'Bai kiem tra thuong xuyen khong duoc ghep lop.',
                'errors' => [
                    'subject_lesson_id' => ['Bai kiem tra thuong xuyen khong duoc ghep lop.'],
                ],
            ], 422);
        }

        if (($assignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY || $isRegularTestLesson) && $lessonType !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Khong duoc chon loai tiet hoc cho truong hop nay.',
                'errors' => [
                    'lesson_type' => ['Khong duoc chon loai tiet hoc cho truong hop nay.'],
                ],
            ], 422);
        }

        $candidateIds = collect($validated['candidate_slot_ids'])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $slots = collect([$baseSlot]);
        $candidateSlots = ScheduleSlot::query()
            ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
            ->whereIn('id', $candidateIds)
            ->get();

        if ($candidateSlots->count() !== count($candidateIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot hoac nhieu tiet duoc chon khong thuoc pham vi tong hop hien tai.',
            ], 422);
        }

        if ($candidateSlots->contains(fn (ScheduleSlot $slot) => $slot->assignment_type === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY)) {
            return response()->json([
                'success' => false,
                'message' => 'Tiet tu nghien cuu khong duoc ghep lop.',
                'errors' => [
                    'candidate_slot_ids' => ['Tiet tu nghien cuu khong duoc ghep lop.'],
                ],
            ], 422);
        }

        if ($candidateSlots->contains(fn (ScheduleSlot $slot) => $slot->isRegularTestLesson())) {
            return response()->json([
                'success' => false,
                'message' => 'Bai kiem tra thuong xuyen khong duoc ghep lop.',
                'errors' => [
                    'candidate_slot_ids' => ['Bai kiem tra thuong xuyen khong duoc ghep lop.'],
                ],
            ], 422);
        }

        $slots = $slots->concat($candidateSlots)->values();

        try {
            $group = $this->mergeService->createMergeGroup(
                $slots,
                isset($validated['teacher_id']) ? (int) $validated['teacher_id'] : null,
                $assignmentType,
                isset($validated['room_id']) ? (int) $validated['room_id'] : null,
                $subjectLessonId !== null ? (int) $subjectLessonId : null,
                $validated['content'] ?? null,
                $validated['note'] ?? null,
                $lessonType
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Merge validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Created merge group successfully.',
            'group_id' => $group->id,
            'slot_ids' => $group->scheduleSlots->pluck('id')->values()->all(),
            'teacher_id' => $group->teacher_id,
            'assignment_type' => $group->assignment_type,
            'room_id' => $group->room_id,
            'subject_lesson_id' => $group->subject_lesson_id,
            'lesson_type' => $group->lesson_type,
        ]);
    }

    public function split(Request $request, int $id, int $groupId): JsonResponse
    {
        $monthlySchedule = MonthlySchedule::query()->find($id);
        if (! $monthlySchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Monthly schedule not found.',
            ], 404);
        }

        $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $request->user());
        if ($scope === null) {
            return response()->json([
                'success' => false,
                'message' => 'Khong xac dinh duoc khoa hien tai de tach ghep lop.',
            ], 422);
        }

        $group = ScheduleSlotGroup::query()->find($groupId);

        if (! $group) {
            return response()->json([
                'success' => false,
                'message' => 'Merge group not found in the selected monthly schedule.',
            ], 404);
        }

        $groupMonthlyScheduleIds = $group->scheduleSlots()
            ->pluck('monthly_schedule_id')
            ->filter(fn ($monthlyScheduleId) => is_numeric($monthlyScheduleId))
            ->map(fn ($monthlyScheduleId) => (int) $monthlyScheduleId)
            ->unique()
            ->values()
            ->all();

        if ($groupMonthlyScheduleIds === [] || array_diff($groupMonthlyScheduleIds, $scope['monthly_schedule_ids']) !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Merge group not found in the selected monthly schedule.',
            ], 404);
        }

        $this->mergeService->splitMergeGroup($group);

        return response()->json([
            'success' => true,
            'message' => 'Split merge group successfully.',
        ]);
    }

    private function findMonthlyScheduleSlot(int $monthlyScheduleId, int $slotId): ?ScheduleSlot
    {
        return ScheduleSlot::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->find($slotId);
    }

    private function formatCandidate(ScheduleSlot $candidate): array
    {
        return [
            'id' => $candidate->id,
            'date' => optional($candidate->date)->toDateString(),
            'period_number' => $candidate->period_number,
            'class_id' => $candidate->class_id,
            'class_code' => $candidate->trainingClass?->code,
            'class_name' => $candidate->trainingClass?->name,
            'subject_id' => $candidate->subject_id,
            'subject_code' => $candidate->subjectModel?->code,
            'subject_name' => $candidate->subjectModel?->name,
            'subject_lesson_id' => $candidate->subject_lesson_id,
            'lesson_label' => $candidate->subjectLesson
                ? 'B' . $candidate->subjectLesson->lesson_no . ': ' . $candidate->subjectLesson->title
                : null,
            'teacher_id' => $candidate->teacher_id,
            'assignment_type' => $candidate->assignment_type,
            'lesson_type' => $candidate->lesson_type,
            'teacher_name' => $this->resolveTeacherName($candidate),
            'room_id' => $candidate->room_id,
            'room_code' => $candidate->room?->code,
            'slot_status' => $candidate->slot_status,
        ];
    }

    private function resolveTeacherName(ScheduleSlot $slot): ?string
    {
        if ($slot->assignment_type === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
            return 'Lớp tự nghiên cứu';
        }

        return $slot->teacher?->name;
    }
}
