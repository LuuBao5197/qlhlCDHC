<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotSubgroup;

class AssignMonthlyScheduleSplitController extends Controller
{
    public function __construct(
        private ScheduleSlotSplitService $splitService
    ) {}

    public function split(Request $request, int $id, int $slotId): JsonResponse
    {
        $monthlySchedule = MonthlySchedule::query()->find($id);
        if (! $monthlySchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Monthly schedule not found.',
            ], 404);
        }

        $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $request->user(), $request->integer('department_id') ?: null);
        if ($scope === null) {
            return response()->json([
                'success' => false,
                'message' => 'Khong xac dinh duoc khoa hien tai de chia to.',
            ], 422);
        }

        $slot = ScheduleSlot::query()
            ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
            ->find($slotId);

        if (! $slot) {
            return response()->json([
                'success' => false,
                'message' => 'Slot not found in the selected monthly schedule.',
            ], 404);
        }

        $validated = $request->validate([
            'subgroups' => ['required', 'array', 'min:2'],
            'subgroups.*.group_label' => ['nullable', 'string', 'max:100'],
            'subgroups.*.teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'subgroups.*.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'subgroups.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $subgroups = collect($validated['subgroups'])
            ->map(fn (array $subgroup) => [
                'group_label' => trim((string) ($subgroup['group_label'] ?? '')),
                'teacher_id' => (int) $subgroup['teacher_id'],
                'room_id' => isset($subgroup['room_id']) ? (int) $subgroup['room_id'] : null,
                'note' => $subgroup['note'] ?? null,
            ])
            ->all();

        try {
            $createdSubgroups = $this->splitService->splitSlot($slot, $subgroups);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Split validation failed.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chia to tiet thuc hanh thanh cong.',
            'slot_id' => $slot->id,
            'subgroups' => $createdSubgroups->map(fn (ScheduleSlotSubgroup $subgroup) => $this->formatSubgroup($subgroup))->values(),
        ]);
    }

    public function clearSplit(Request $request, int $id, int $slotId): JsonResponse
    {
        $monthlySchedule = MonthlySchedule::query()->find($id);
        if (! $monthlySchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Monthly schedule not found.',
            ], 404);
        }

        $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $request->user(), $request->integer('department_id') ?: null);
        if ($scope === null) {
            return response()->json([
                'success' => false,
                'message' => 'Khong xac dinh duoc khoa hien tai de bo chia to.',
            ], 422);
        }

        $slot = ScheduleSlot::query()
            ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
            ->find($slotId);

        if (! $slot) {
            return response()->json([
                'success' => false,
                'message' => 'Slot not found in the selected monthly schedule.',
            ], 404);
        }

        $this->splitService->clearSplit($slot);

        return response()->json([
            'success' => true,
            'message' => 'Da bo chia to tiet thuc hanh.',
        ]);
    }

    private function formatSubgroup(ScheduleSlotSubgroup $subgroup): array
    {
        return [
            'id' => $subgroup->id,
            'group_label' => $subgroup->group_label,
            'teacher_id' => $subgroup->teacher_id,
            'teacher_name' => $subgroup->teacher?->name,
            'room_id' => $subgroup->room_id,
            'room_code' => $subgroup->room?->code,
            'note' => $subgroup->note,
        ];
    }
}
