<?php

namespace Modules\Schedule\Application\ViewScheduleCalendar;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleExportPeriod;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Subject;

class ScheduleCalendarSlotLoader
{
    /**
     * Lay cac slot (tiet mon hoc, su kien, tiet bi huy do nghi le) trong khoang ngay theo pham vi xem.
     *
     * @return Collection<int, ScheduleSlot>
     */
    public function load(string $scope, ?int $scopeId, ?int $teacherId, DepartmentScheduleExportPeriod $period): Collection
    {
        $range = [$period->start->startOfDay(), $period->end->endOfDay()];

        $query = ScheduleSlot::query()
            ->with([
                'trainingClass',
                'teacher',
                'subjectModel',
                'subjectLesson',
                'room',
                'semesterEvent',
                'holidayCalendar',
                'scheduleSlotGroup.teacher',
                'scheduleSlotGroup.room',
                'scheduleSlotSubgroups.teacher',
                'scheduleSlotSubgroups.room',
            ])
            ->whereIn('slot_type', ['subject', 'event'])
            ->whereNotNull('period_number')
            ->whereBetween('date', $range)
            // Tiet bi huy chi hien thi khi huy do nghi le.
            ->where(function (Builder $q): void {
                $q->where('slot_status', '!=', 'cancelled')->orWhereNotNull('holiday_calendar_id');
            });

        match ($scope) {
            ScheduleCalendarAccess::SCOPE_DEPARTMENT => $this->scopeToDepartment($query, (int) $scopeId, $range),
            ScheduleCalendarAccess::SCOPE_CLASS => $query->where('class_id', (int) $scopeId),
            ScheduleCalendarAccess::SCOPE_MINE => $this->scopeToTeacher($query, (int) $teacherId),
            default => null,
        };

        return $query
            ->orderBy('date')
            ->orderBy('period_number')
            ->orderBy('class_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Slot mon hoc loc theo khoa cua mon; chi lay them ngay nghi le (slot su kien holiday, khong co mon)
     * cua cac lop co tiet mon cua khoa trong ky. Cac su kien khac khong hien thi o view theo khoa.
     *
     * @param array{0: \Carbon\CarbonImmutable, 1: \Carbon\CarbonImmutable} $range
     */
    private function scopeToDepartment(Builder $query, int $departmentId, array $range): void
    {
        $subjectIds = Subject::query()->where('department_id', $departmentId)->pluck('id')->all();

        $query->where(function (Builder $q) use ($subjectIds, $range): void {
            $q->whereIn('subject_id', $subjectIds)
                ->orWhere(function (Builder $events) use ($subjectIds, $range): void {
                    $events->where('slot_type', 'event')
                        ->where('event_type', 'holiday')
                        ->whereIn('class_id', function ($sub) use ($subjectIds, $range): void {
                            $sub->select('class_id')
                                ->from('schedule_slots')
                                ->whereIn('subject_id', $subjectIds)
                                ->whereBetween('date', $range);
                        });
                });
        });
    }

    private function scopeToTeacher(Builder $query, int $teacherId): void
    {
        $query->where(function (Builder $q) use ($teacherId): void {
            $q->where('teacher_id', $teacherId)
                ->orWhereHas('scheduleSlotGroup', fn ($group) => $group->where('teacher_id', $teacherId))
                ->orWhereHas('scheduleSlotSubgroups', fn ($sub) => $sub->where('teacher_id', $teacherId));
        });
    }
}
