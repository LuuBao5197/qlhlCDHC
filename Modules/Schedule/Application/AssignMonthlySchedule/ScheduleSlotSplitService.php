<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotSubgroup;
use Modules\Training\Models\Room;
use Modules\Training\Models\Teacher;

class ScheduleSlotSplitService
{
    /**
     * @param array<int, array{group_label:string, teacher_id:int, room_id:?int, note:?string}> $subgroups
     */
    public function splitSlot(ScheduleSlot $slot, array $subgroups): Collection
    {
        $this->assertSlotIsSplittable($slot);

        if (count($subgroups) < 2) {
            throw ValidationException::withMessages([
                'subgroups' => 'Can chia thanh toi thieu 2 to.',
            ]);
        }

        $this->assertTeachersExist($subgroups);
        $this->assertRoomsExist($subgroups);
        $this->assertNoDuplicateTeacherWithinPayload($subgroups);
        $this->assertNoResourceConflict($slot, $subgroups);

        return DB::transaction(function () use ($slot, $subgroups): Collection {
            $slot->scheduleSlotSubgroups()->delete();

            foreach ($subgroups as $index => $subgroup) {
                ScheduleSlotSubgroup::query()->create([
                    'schedule_slot_id' => $slot->id,
                    'group_label' => $subgroup['group_label'] !== '' ? $subgroup['group_label'] : ('To ' . ($index + 1)),
                    'teacher_id' => $subgroup['teacher_id'],
                    'room_id' => $subgroup['room_id'] ?? null,
                    'note' => $subgroup['note'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }

            $slot->teacher_id = null;
            $slot->room_id = null;
            $slot->save();

            return $slot->scheduleSlotSubgroups()->get();
        });
    }

    public function clearSplit(ScheduleSlot $slot): void
    {
        DB::transaction(function () use ($slot): void {
            $slot->scheduleSlotSubgroups()->delete();
        });
    }

    private function assertSlotIsSplittable(ScheduleSlot $slot): void
    {
        if ($slot->slot_type !== 'subject') {
            throw ValidationException::withMessages([
                'slot' => 'Chi tiet hoc (slot_type = subject) moi duoc chia to.',
            ]);
        }

        if ($slot->assignment_source !== 'internal') {
            throw ValidationException::withMessages([
                'slot' => 'Chi tiet phan cong noi bo cua khoa moi duoc chia to.',
            ]);
        }

        if ($slot->lesson_type !== ScheduleSlot::LESSON_TYPE_PRACTICE) {
            throw ValidationException::withMessages([
                'slot' => 'Chi tiet thuc hanh moi duoc chia to.',
            ]);
        }

        if (($slot->slot_status ?? 'planned') === 'cancelled') {
            throw ValidationException::withMessages([
                'slot' => 'Tiet da huy (nghi le/tet) khong duoc chia to.',
            ]);
        }

        if ($slot->schedule_slot_group_id !== null) {
            throw ValidationException::withMessages([
                'slot' => 'Tiet dang thuoc mot nhom ghep lop, khong the chia to.',
            ]);
        }

        if ($slot->dailyTrainingLogs()->exists()) {
            throw ValidationException::withMessages([
                'slot' => 'Tiet da co nhat ky giang day, khong the chia to.',
            ]);
        }
    }

    /**
     * @param array<int, array{teacher_id:int}> $subgroups
     */
    private function assertTeachersExist(array $subgroups): void
    {
        $teacherIds = collect($subgroups)->pluck('teacher_id')->unique()->values();
        $existingCount = Teacher::query()->whereIn('id', $teacherIds)->count();

        if ($existingCount !== $teacherIds->count()) {
            throw ValidationException::withMessages([
                'subgroups' => 'Mot hoac nhieu giang vien duoc chon khong ton tai.',
            ]);
        }
    }

    /**
     * @param array<int, array{room_id:?int}> $subgroups
     */
    private function assertRoomsExist(array $subgroups): void
    {
        $roomIds = collect($subgroups)
            ->pluck('room_id')
            ->filter(fn ($roomId) => $roomId !== null)
            ->unique()
            ->values();

        if ($roomIds->isEmpty()) {
            return;
        }

        $existingCount = Room::query()->whereIn('id', $roomIds)->count();

        if ($existingCount !== $roomIds->count()) {
            throw ValidationException::withMessages([
                'subgroups' => 'Mot hoac nhieu phong hoc duoc chon khong ton tai.',
            ]);
        }
    }

    /**
     * @param array<int, array{teacher_id:int}> $subgroups
     */
    private function assertNoDuplicateTeacherWithinPayload(array $subgroups): void
    {
        $teacherIds = collect($subgroups)->pluck('teacher_id')->values();

        if ($teacherIds->unique()->count() !== $teacherIds->count()) {
            throw ValidationException::withMessages([
                'subgroups' => 'Khong the phan cong trung giang vien giua cac to cua cung mot tiet.',
            ]);
        }
    }

    /**
     * @param array<int, array{teacher_id:int, room_id:?int}> $subgroups
     */
    private function assertNoResourceConflict(ScheduleSlot $slot, array $subgroups): void
    {
        $date = $slot->date?->format('Y-m-d');
        $period = $slot->period_number;

        if ($date === null || $period === null) {
            return;
        }

        $teacherIds = collect($subgroups)->pluck('teacher_id')->unique()->values()->all();
        $roomIds = collect($subgroups)->pluck('room_id')->filter(fn ($roomId) => $roomId !== null)->unique()->values()->all();

        if ($teacherIds !== []) {
            $teacherConflictExists = ScheduleSlot::query()
                ->whereIn('teacher_id', $teacherIds)
                ->whereDate('date', $date)
                ->where('period_number', $period)
                ->where('id', '!=', $slot->id)
                ->exists();

            if ($teacherConflictExists) {
                throw ValidationException::withMessages([
                    'subgroups' => 'Mot hoac nhieu giang vien da duoc phan cong o cung ngay tiet nay tren mot tiet khac.',
                ]);
            }

            $subgroupTeacherConflictExists = ScheduleSlotSubgroup::query()
                ->whereIn('teacher_id', $teacherIds)
                ->where('schedule_slot_id', '!=', $slot->id)
                ->whereHas('scheduleSlot', function ($query) use ($date, $period): void {
                    $query->whereDate('date', $date)->where('period_number', $period);
                })
                ->exists();

            if ($subgroupTeacherConflictExists) {
                throw ValidationException::withMessages([
                    'subgroups' => 'Mot hoac nhieu giang vien da duoc phan cong o cung ngay tiet nay o mot to khac.',
                ]);
            }
        }

        if ($roomIds !== []) {
            $roomConflictExists = ScheduleSlot::query()
                ->whereIn('room_id', $roomIds)
                ->whereDate('date', $date)
                ->where('period_number', $period)
                ->where('id', '!=', $slot->id)
                ->exists();

            if ($roomConflictExists) {
                throw ValidationException::withMessages([
                    'subgroups' => 'Mot hoac nhieu phong hoc da duoc su dung o cung ngay tiet nay tren mot tiet khac.',
                ]);
            }

            $subgroupRoomConflictExists = ScheduleSlotSubgroup::query()
                ->whereIn('room_id', $roomIds)
                ->where('schedule_slot_id', '!=', $slot->id)
                ->whereHas('scheduleSlot', function ($query) use ($date, $period): void {
                    $query->whereDate('date', $date)->where('period_number', $period);
                })
                ->exists();

            if ($subgroupRoomConflictExists) {
                throw ValidationException::withMessages([
                    'subgroups' => 'Mot hoac nhieu phong hoc da duoc su dung o cung ngay tiet nay o mot to khac.',
                ]);
            }
        }
    }
}
