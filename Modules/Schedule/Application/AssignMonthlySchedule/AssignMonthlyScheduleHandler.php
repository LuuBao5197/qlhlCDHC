<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlotGroup;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Subject;
use Throwable;

class AssignMonthlyScheduleHandler
{
    /**
     * Save monthly slot assignments from department staff.
     */
    public function handle(AssignMonthlyScheduleRequest $request, int $id)
    {
        $monthlySchedule = MonthlySchedule::query()->findOrFail($id);
        $validated = $request->validated();
        $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $request->user());

        if ($scope === null) {
            return back()->withInput()->with('error', 'Khong xac dinh duoc khoa hien tai de tong hop phan cong.');
        }

        $slotIds = collect($validated['slots'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validIds = ScheduleSlot::query()
            ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
            ->whereIn('id', $slotIds)
            ->pluck('id')
            ->all();
        $validIdMap = array_flip($validIds);

        try {
            DB::transaction(function () use ($request, $monthlySchedule, $validated, $validIdMap, $validIds): void {
                $subjectIds = collect($validated['slots'])->pluck('subject_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
                $subjects = Subject::query()
                    ->whereIn('id', $subjectIds)
                    ->get()
                    ->keyBy('id');

                foreach ($validated['slots'] as $slotData) {
                    $slotId = (int) $slotData['id'];
                    if (!isset($validIdMap[$slotId])) {
                        continue;
                    }

                    /** @var ScheduleSlot $slot */
                    $slot = ScheduleSlot::query()->findOrFail($slotId);
                    $isEventSlot = ($slot->slot_type ?? 'subject') === 'event';

                    $subjectId = $slotData['subject_id'] ?? null;
                    $subjectLabel = null;
                    if (!$isEventSlot && $subjectId) {
                        $subject = $subjects->get((int) $subjectId);
                        $subjectLabel = $subject?->code ?: $subject?->name;
                    }

                    if (!$isEventSlot) {
                        if (array_key_exists('teacher_id', $slotData)) {
                            $slot->teacher_id = $slotData['teacher_id'] ?? null;
                        }
                        if (array_key_exists('subject_id', $slotData)) {
                            $slot->subject_id = $subjectId;
                        }
                        if (array_key_exists('subject_lesson_id', $slotData)) {
                            $slot->subject_lesson_id = $slotData['subject_lesson_id'] ?? null;
                        }
                        if (array_key_exists('room_id', $slotData)) {
                            $slot->room_id = $slotData['room_id'] ?? null;
                        }
                    }

                    if (array_key_exists('content', $slotData)) {
                        $slot->content = $slotData['content'] ?? null;
                    }
                    if (array_key_exists('note', $slotData)) {
                        $slot->note = $slotData['note'] ?? null;
                    }
                    if (array_key_exists('slot_status', $slotData)) {
                        $slot->slot_status = $slotData['slot_status'] ?? 'planned';
                    }

                    if (!$isEventSlot && $subjectLabel !== null) {
                        $slot->subject = $subjectLabel;
                    }

                    if (!$isEventSlot && $monthlySchedule->class_id !== null && $slot->class_id === null) {
                        $slot->class_id = $monthlySchedule->class_id;
                    }

                    $slot->save();
                }

                $activeGroupIds = ScheduleSlot::query()
                    ->with('scheduleSlotGroup')
                    ->where('monthly_schedule_id', $monthlySchedule->id)
                    ->whereIn('id', $validIds)
                    ->get()
                    ->filter(fn (ScheduleSlot $slot) => $slot->scheduleSlotGroup?->status === 'active')
                    ->pluck('schedule_slot_group_id')
                    ->filter(fn ($groupId) => is_numeric($groupId))
                    ->map(fn ($groupId) => (int) $groupId)
                    ->unique()
                    ->values();

                if ($activeGroupIds->isNotEmpty()) {
                    $groups = ScheduleSlotGroup::query()
                        ->whereIn('id', $activeGroupIds)
                        ->get();

                    foreach ($groups as $group) {
                        $groupSlot = ScheduleSlot::query()
                            ->where('schedule_slot_group_id', $group->id)
                            ->orderBy('id')
                            ->first();

                        if (! $groupSlot) {
                            continue;
                        }

                        $group->subject_id = $groupSlot->subject_id;
                        $group->subject_lesson_id = $groupSlot->subject_lesson_id;
                        $group->teacher_id = $groupSlot->teacher_id;
                        $group->room_id = $groupSlot->room_id;
                        $group->save();
                    }
                }

                $touchUpdates = [];
                if ($monthlySchedule->created_by === null && $request->user()) {
                    $touchUpdates['created_by'] = $request->user()->id;
                }

                if ($monthlySchedule->department_id === null && $request->user()?->department_id !== null) {
                    $touchUpdates['department_id'] = $request->user()->department_id;
                }

                if ($touchUpdates !== []) {
                    $monthlySchedule->update($touchUpdates);
                }
            });
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Khong the luu phan cong lich thang: ' . $exception->getMessage());
        }

        return back()->with('success', 'Da luu phan cong lich giang day theo thang thanh cong.');
    }
}
