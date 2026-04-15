<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
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

        $slotIds = collect($validated['slots'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validIds = $monthlySchedule->scheduleSlots()->whereIn('id', $slotIds)->pluck('id')->all();
        $validIdMap = array_flip($validIds);

        try {
            DB::transaction(function () use ($request, $monthlySchedule, $validated, $validIdMap): void {
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

                    $subjectId = $slotData['subject_id'] ?? null;
                    $subjectLabel = null;
                    if ($subjectId) {
                        $subject = $subjects->get((int) $subjectId);
                        $subjectLabel = $subject?->code ?: $subject?->name;
                    }

                    $slot->teacher_id = $slotData['teacher_id'] ?? null;
                    $slot->subject_id = $subjectId;
                    $slot->subject_lesson_id = $slotData['subject_lesson_id'] ?? null;
                    $slot->room_id = $slotData['room_id'] ?? null;
                    $slot->content = $slotData['content'] ?? null;
                    $slot->note = $slotData['note'] ?? null;
                    $slot->slot_status = $slotData['slot_status'] ?? 'planned';

                    if ($subjectLabel !== null) {
                        $slot->subject = $subjectLabel;
                    }

                    if ($monthlySchedule->class_id !== null && $slot->class_id === null) {
                        $slot->class_id = $monthlySchedule->class_id;
                    }

                    $slot->save();
                }

                $touchUpdates = [];
                if ($monthlySchedule->created_by === null && $request->user()) {
                    $touchUpdates['created_by'] = $request->user()->id;
                }

                if ($monthlySchedule->department_id === null && $request->user()?->department_id !== null) {
                    $touchUpdates['department_id'] = $request->user()->department_id;
                }

                if ($monthlySchedule->status === 'returned') {
                    $touchUpdates['status'] = 'draft';
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
