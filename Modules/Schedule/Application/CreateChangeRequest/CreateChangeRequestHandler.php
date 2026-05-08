<?php

namespace Modules\Schedule\Application\CreateChangeRequest;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\ChangeRequest;
use Throwable;

class CreateChangeRequestHandler
{
    /**
     * Store a new change request with one or more request items.
     */
    public function handle(CreateChangeRequestRequest $request)
    {
        $validated = $request->validated();
        $monthlyScheduleId = (int) $validated['monthly_schedule_id'];
        $selectedSlots = collect($validated['selected_slots'])
            ->map(static fn (array $item) => [
                'slot_id' => (int) ($item['slot_id'] ?? 0),
                'new_payload' => is_array($item['new_payload'] ?? null) ? $item['new_payload'] : [],
            ])
            ->filter(static fn (array $item) => $item['slot_id'] > 0)
            ->values();

        $monthlySchedule = MonthlySchedule::query()->findOrFail($monthlyScheduleId);

        $slotIds = $selectedSlots->pluck('slot_id')->unique()->values();

        $slots = ScheduleSlot::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->whereIn('id', $slotIds)
            ->orderBy('id')
            ->get();

        if ($slots->count() !== $slotIds->count()) {
            return back()->withInput()->with('error', 'Co slot ID khong thuoc lich thang da chon.');
        }

        $slotMap = $slots->keyBy('id');

        if ($selectedSlots->contains(static fn (array $item) => ! $slotMap->has($item['slot_id']))) {
            return back()->withInput()->with('error', 'Du lieu slot de nghi thay doi khong hop le.');
        }

        try {
            DB::transaction(function () use ($request, $validated, $monthlySchedule, $selectedSlots, $slotMap): void {
                $firstSelected = $selectedSlots->first();
                $firstSlot = $firstSelected ? $slotMap->get($firstSelected['slot_id']) : null;
                $firstPayload = $firstSelected['new_payload'] ?? [];

                $changeRequest = ChangeRequest::query()->create([
                    'monthly_schedule_id' => $monthlySchedule->id,
                    'schedule_slot_id' => $firstSlot?->id,
                    'requested_by' => $request->user()?->id,
                    'reason' => (string) $validated['reason'],
                    'old_payload' => $firstSlot ? $this->extractSlotPayload($firstSlot) : null,
                    'new_payload' => $firstPayload,
                    'status' => 'pending',
                    'apply_mode' => (string) ($validated['apply_mode'] ?? 'all_or_none'),
                    'apply_changes' => true,
                    'apply_summary' => null,
                    'submitted_at' => now(),
                    'resolved_at' => null,
                ]);

                foreach ($selectedSlots as $selectedSlot) {
                    /** @var ScheduleSlot|null $slot */
                    $slot = $slotMap->get($selectedSlot['slot_id']);

                    if (! $slot) {
                        continue;
                    }

                    $changeRequest->changeRequestItems()->create([
                        'schedule_slot_id' => $slot->id,
                        'old_payload' => $this->extractSlotPayload($slot),
                        'new_payload' => $selectedSlot['new_payload'],
                        'apply_status' => null,
                        'apply_error' => null,
                        'applied_at' => null,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            return back()->withInput()->with(
                'error',
                'Khong the tao phieu de nghi thay doi: ' . $exception->getMessage()
            );
        }

        return back()->with('success', 'Da tao phieu de nghi thay doi lich giang day thanh cong.');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSlotPayload(ScheduleSlot $slot): array
    {
        return [
            'class_id' => $slot->class_id,
            'teacher_id' => $slot->teacher_id,
            'subject_id' => $slot->subject_id,
            'subject_lesson_id' => $slot->subject_lesson_id,
            'room_id' => $slot->room_id,
            'date' => $slot->date?->format('Y-m-d'),
            'day_of_week' => $slot->day_of_week,
            'period' => $slot->period,
            'period_number' => $slot->period_number,
            'subject' => $slot->subject,
            'content' => $slot->content,
            'slot_status' => $slot->slot_status,
            'actual_content' => $slot->actual_content,
            'note' => $slot->note,
        ];
    }
}
