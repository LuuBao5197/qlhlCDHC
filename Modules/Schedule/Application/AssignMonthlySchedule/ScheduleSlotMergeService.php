<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;
use Modules\Training\Models\Room;
use Modules\Training\Models\Teacher;

class ScheduleSlotMergeService
{
    /**
     * @param array<int> $allowedMonthlyScheduleIds
     */
    public function getMergeCandidates(ScheduleSlot $baseSlot, array $allowedMonthlyScheduleIds = []): Collection
    {
        if (! $this->isMergeAnchorSlot($baseSlot)) {
            return collect();
        }

        if (
            $baseSlot->monthly_schedule_id === null
            || $baseSlot->date === null
            || $baseSlot->period_number === null
            || $baseSlot->subject_id === null
            || $baseSlot->class_id === null
        ) {
            return collect();
        }

        return ScheduleSlot::query()
            ->when(
                $allowedMonthlyScheduleIds !== [],
                fn ($query) => $query->whereIn('monthly_schedule_id', $allowedMonthlyScheduleIds),
                fn ($query) => $query->where('monthly_schedule_id', $baseSlot->monthly_schedule_id)
            )
            ->whereDate('date', optional($baseSlot->date)->format('Y-m-d'))
            ->where('period_number', $baseSlot->period_number)
            ->where('subject_id', $baseSlot->subject_id)
            ->whereNull('schedule_slot_group_id')
            ->where('class_id', '!=', $baseSlot->class_id)
            ->when(
                $baseSlot->subject_lesson_id !== null,
                fn ($query) => $query->where('subject_lesson_id', $baseSlot->subject_lesson_id),
                fn ($query) => $query->whereNull('subject_lesson_id')
            )
            ->where('slot_type', '!=', 'event')
            ->where('slot_status', '!=', 'cancelled')
            ->orderBy('class_id')
            ->get();
    }

    public function canMergeSlots(ScheduleSlot $baseSlot, ScheduleSlot $candidateSlot): bool
    {
        if ($baseSlot->id === $candidateSlot->id) {
            return false;
        }

        if (! $this->isMergeAnchorSlot($baseSlot) || ! $this->isMergeAnchorSlot($candidateSlot)) {
            return false;
        }

        if (! $this->sameDate($baseSlot, $candidateSlot)) {
            return false;
        }

        if ((int) $baseSlot->period_number !== (int) $candidateSlot->period_number) {
            return false;
        }

        if ((int) $baseSlot->subject_id !== (int) $candidateSlot->subject_id) {
            return false;
        }

        if ((int) $baseSlot->class_id === (int) $candidateSlot->class_id) {
            return false;
        }

        if (! $this->sameLesson($baseSlot, $candidateSlot)) {
            return false;
        }

        if (! $this->canMergeByLessonMode($baseSlot, $candidateSlot)) {
            return false;
        }

        return true;
    }

    public function validateMergeSlots(Collection|array $slots): void
    {
        $slots = $this->normalizeSlots($slots);

        if ($slots->count() < 2) {
            throw ValidationException::withMessages([
                'slots' => 'Can toi thieu 2 tiet hop le de ghep lop.',
            ]);
        }

        $baseSlot = $slots->first();
        if (! $baseSlot instanceof ScheduleSlot) {
            throw ValidationException::withMessages([
                'slots' => 'Danh sach tiet hoc khong hop le.',
            ]);
        }

        if (! $this->isMergeAnchorSlot($baseSlot)) {
            throw ValidationException::withMessages([
                'slots.0' => 'Tiet hoc goc khong the ghep lop.',
            ]);
        }

        foreach ($slots as $index => $slot) {
            if (! $slot instanceof ScheduleSlot) {
                throw ValidationException::withMessages([
                    "slots.{$index}" => 'Danh sach tiet hoc khong hop le.',
                ]);
            }

            if ($slot->id === $baseSlot->id) {
                continue;
            }

            if (! $this->canMergeSlots($baseSlot, $slot)) {
                throw ValidationException::withMessages([
                    "slots.{$index}" => 'Tiet hoc nay khong the ghep voi tiet hoc goc.',
                ]);
            }
        }

    }

    public function createMergeGroup(
        Collection|array $slots,
        ?int $teacherId = null,
        ?int $roomId = null,
        ?int $subjectLessonId = null,
        ?string $content = null,
        ?string $note = null
    ): ScheduleSlotGroup
    {
        $slots = $this->normalizeSlots($slots);
        $this->validateMergeSlots($slots);

        return DB::transaction(function () use ($slots, $teacherId, $roomId, $subjectLessonId, $content, $note): ScheduleSlotGroup {
            $resolvedTeacherId = $this->resolveTeacherId($slots, $teacherId);
            $resolvedRoomId = $this->resolveRoomId($slots, $roomId);
            $baseSlot = $slots->first();

            if (! $baseSlot instanceof ScheduleSlot) {
                throw ValidationException::withMessages([
                    'slots' => 'Danh sach tiet hoc khong hop le.',
                ]);
            }

            $this->validateResolvedResourceAvailability($slots, $resolvedTeacherId, $resolvedRoomId);

            $group = ScheduleSlotGroup::query()->create([
                'monthly_schedule_id' => $baseSlot->monthly_schedule_id,
                'date' => optional($baseSlot->date)->toDateString(),
                'period_number' => $baseSlot->period_number,
                'subject_id' => $baseSlot->subject_id,
                'subject_lesson_id' => $subjectLessonId ?? $baseSlot->subject_lesson_id,
                'teacher_id' => $resolvedTeacherId,
                'room_id' => $resolvedRoomId,
                'status' => 'active',
                'note' => $note ?? $baseSlot->note,
                'created_by' => auth()->id(),
            ]);

            $resolvedContent = $content ?? $baseSlot->content;
            $resolvedNote = $note ?? $baseSlot->note;

            foreach ($slots as $slot) {
                /** @var ScheduleSlot $slot */
                $slot->schedule_slot_group_id = $group->id;
                $slot->subject_id = $baseSlot->subject_id;
                $slot->subject_lesson_id = $subjectLessonId ?? $baseSlot->subject_lesson_id;
                $slot->teacher_id = $resolvedTeacherId;
                $slot->room_id = $resolvedRoomId;
                $slot->content = $resolvedContent;
                $slot->note = $resolvedNote;
                $slot->save();
            }

            return $group->refresh()->load('scheduleSlots');
        });
    }

    public function splitMergeGroup(ScheduleSlotGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $group->loadMissing('scheduleSlots');

            foreach ($group->scheduleSlots as $slot) {
                /** @var ScheduleSlot $slot */
                $slot->schedule_slot_group_id = null;
                $slot->save();
            }

            $group->update([
                'status' => 'inactive',
            ]);
        });
    }

    private function normalizeSlots(Collection|array $slots): Collection
    {
        return collect($slots)
            ->filter(fn ($slot) => $slot instanceof ScheduleSlot)
            ->unique(fn (ScheduleSlot $slot) => $slot->id)
            ->values();
    }

    private function isMergeAnchorSlot(ScheduleSlot $slot): bool
    {
        if ($slot->slot_type === 'event') {
            return false;
        }

        if (($slot->slot_status ?? 'planned') === 'cancelled') {
            return false;
        }

        if ($slot->schedule_slot_group_id !== null) {
            return false;
        }

        if ($this->isPracticeSlot($slot)) {
            return false;
        }

        return true;
    }

    private function sameDate(ScheduleSlot $first, ScheduleSlot $second): bool
    {
        return optional($first->date)->toDateString() === optional($second->date)->toDateString();
    }

    private function sameLesson(ScheduleSlot $first, ScheduleSlot $second): bool
    {
        if ($first->subject_lesson_id === null && $second->subject_lesson_id === null) {
            return true;
        }

        return $first->subject_lesson_id !== null
            && $second->subject_lesson_id !== null
            && (int) $first->subject_lesson_id === (int) $second->subject_lesson_id;
    }

    private function resolveTeacherId(Collection $slots, ?int $teacherId): ?int
    {
        if ($teacherId !== null) {
            if (! Teacher::query()->whereKey($teacherId)->exists()) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'Giang vien duoc chon khong ton tai.',
                ]);
            }

            return $teacherId;
        }

        $teacherIds = $slots
            ->pluck('teacher_id')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($teacherIds->count() > 1) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Khong the tu dong chon giang vien khi cac tiet co nhieu giang vien khac nhau.',
            ]);
        }

        return $teacherIds->first() ? (int) $teacherIds->first() : null;
    }

    private function resolveRoomId(Collection $slots, ?int $roomId): ?int
    {
        if ($roomId !== null) {
            if (! Room::query()->whereKey($roomId)->exists()) {
                throw ValidationException::withMessages([
                    'room_id' => 'Phong hoc duoc chon khong ton tai.',
                ]);
            }

            return $roomId;
        }

        $roomIds = $slots
            ->pluck('room_id')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($roomIds->count() > 1) {
            throw ValidationException::withMessages([
                'room_id' => 'Khong the tu dong chon phong hoc khi cac tiet co nhieu phong khac nhau.',
            ]);
        }

        return $roomIds->first() ? (int) $roomIds->first() : null;
    }

    private function validateResolvedResourceAvailability(Collection $slots, ?int $teacherId, ?int $roomId): void
    {
        /** @var ScheduleSlot|null $baseSlot */
        $baseSlot = $slots->first();
        if (! $baseSlot instanceof ScheduleSlot) {
            return;
        }

        $slotIds = $slots
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $date = optional($baseSlot->date)->format('Y-m-d');
        $period = $baseSlot->period_number;

        if ($date === null || $period === null) {
            return;
        }

        if ($teacherId !== null) {
            $teacherConflict = ScheduleSlot::query()
                ->where('teacher_id', $teacherId)
                ->whereDate('date', $date)
                ->where('period_number', $period)
                ->whereNotIn('id', $slotIds)
                ->first();

            if ($teacherConflict) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'Giang vien da duoc phan cong o cung ngay tiet nay.',
                ]);
            }
        }

        if ($roomId !== null) {
            $roomConflict = ScheduleSlot::query()
                ->where('room_id', $roomId)
                ->whereDate('date', $date)
                ->where('period_number', $period)
                ->whereNotIn('id', $slotIds)
                ->first();

            if ($roomConflict) {
                throw ValidationException::withMessages([
                    'room_id' => 'Phong hoc da duoc su dung o cung ngay tiet nay.',
                ]);
            }
        }
    }

    private function isPracticeSlot(ScheduleSlot $slot): bool
    {
        // TODO: Project hien chua co field dang tin cay de phan biet ly thuyet/thuc hanh.
        // Tam thoi khong suy dien practice de tranh bua ra logic sai schema.
        return false;
    }

    private function canMergeByLessonMode(ScheduleSlot $baseSlot, ScheduleSlot $candidateSlot): bool
    {
        if ($this->isPracticeSlot($baseSlot) || $this->isPracticeSlot($candidateSlot)) {
            return false;
        }

        return true;
    }
}
