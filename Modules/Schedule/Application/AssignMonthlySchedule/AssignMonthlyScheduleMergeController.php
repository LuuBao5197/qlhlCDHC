<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;

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
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'content' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

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

        $slots = $slots->concat($candidateSlots)->values();

        try {
            $group = $this->mergeService->createMergeGroup(
                $slots,
                isset($validated['teacher_id']) ? (int) $validated['teacher_id'] : null,
                isset($validated['room_id']) ? (int) $validated['room_id'] : null,
                isset($validated['subject_lesson_id']) ? (int) $validated['subject_lesson_id'] : null,
                $validated['content'] ?? null,
                $validated['note'] ?? null
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
            'room_id' => $group->room_id,
            'subject_lesson_id' => $group->subject_lesson_id,
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
            'teacher_name' => $candidate->teacher?->name,
            'room_id' => $candidate->room_id,
            'room_code' => $candidate->room?->code,
            'slot_status' => $candidate->slot_status,
        ];
    }
}
