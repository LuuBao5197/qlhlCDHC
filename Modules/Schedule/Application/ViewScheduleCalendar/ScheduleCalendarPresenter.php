<?php

namespace Modules\Schedule\Application\ViewScheduleCalendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Schedule\Application\ExportDepartmentSchedule\DepartmentScheduleExportPeriod;
use Modules\Schedule\Application\Shared\ScheduleSlotLabels;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;

/**
 * Chuyen ScheduleSlot thanh luoi lich: ngay -> tiet -> danh sach muc hien thi.
 *
 * Moi muc co "kind": assigned | unassigned | self_study | event | holiday.
 */
class ScheduleCalendarPresenter
{
    public const MIN_PERIODS = 10;

    private const LESSON_TYPE_LABELS = [
        ScheduleSlot::LESSON_TYPE_THEORY => 'Lý thuyết',
        ScheduleSlot::LESSON_TYPE_PRACTICE => 'Thực hành',
    ];

    private const EVENT_TYPE_LABELS = [
        'holiday' => 'Nghỉ lễ',
        'review' => 'Ôn tập',
        'exam' => 'Thi',
        'other' => 'Sự kiện',
    ];

    /**
     * @param Collection<int, ScheduleSlot> $slots
     * @return array{days: array<int, array{date: CarbonImmutable, cells: array<int, array<int, array<string, mixed>>>}>, periods: array<int, int>}
     */
    public function present(Collection $slots, DepartmentScheduleExportPeriod $period): array
    {
        $days = [];
        for ($date = $period->start; $date->lte($period->end); $date = $date->addDay()) {
            $days[$date->format('Y-m-d')] = ['date' => $date, 'cells' => []];
        }

        $maxPeriod = self::MIN_PERIODS;
        foreach ($this->buildUnits($slots) as $unit) {
            $dayKey = $unit['date']->format('Y-m-d');
            if (! isset($days[$dayKey])) {
                continue;
            }

            foreach ($this->itemsForUnit($unit['slots']) as $item) {
                $days[$dayKey]['cells'][$unit['period']][] = $item;
            }
            $maxPeriod = max($maxPeriod, $unit['period']);
        }

        return ['days' => array_values($days), 'periods' => range(1, $maxPeriod)];
    }

    /**
     * Cac slot cung ngay, cung tiet, cung nhom ghep (hoac 1 slot le) thanh 1 unit.
     *
     * @param Collection<int, ScheduleSlot> $slots
     * @return array<string, array{date: CarbonImmutable, period: int, slots: array<int, ScheduleSlot>}>
     */
    private function buildUnits(Collection $slots): array
    {
        $units = [];
        foreach ($slots as $slot) {
            if ($slot->date === null || $slot->period_number === null) {
                continue;
            }

            $date = CarbonImmutable::parse($slot->date)->startOfDay();
            $period = (int) $slot->period_number;
            $groupKey = $slot->schedule_slot_group_id !== null ? 'g' . $slot->schedule_slot_group_id : 's' . $slot->id;
            $key = $date->format('Y-m-d') . '|' . $period . '|' . $groupKey;

            $units[$key] ??= ['date' => $date, 'period' => $period, 'slots' => []];
            $units[$key]['slots'][] = $slot;
        }

        return $units;
    }

    /**
     * @param array<int, ScheduleSlot> $unitSlots
     * @return array<int, array<string, mixed>>
     */
    private function itemsForUnit(array $unitSlots): array
    {
        $base = $unitSlots[0];
        $group = $base->scheduleSlotGroup;
        $classes = implode(', ', array_values(array_unique(array_filter(
            array_map(static fn (ScheduleSlot $s) => ScheduleSlotLabels::classLabel($s), $unitSlots)
        ))));

        $common = [
            'classes' => $classes,
            'subject' => (string) ($base->subjectModel?->name ?? $base->subject ?? ''),
            'lesson' => '',
            'teacher' => '',
            'room' => '',
            'lessonType' => '',
            'note' => '',
        ];

        if ($base->slot_type === 'event') {
            return [$this->eventItem($base, $common)];
        }

        if ($base->slot_status === 'cancelled') {
            $holiday = $base->holidayCalendar?->name;

            return [['kind' => 'holiday'] + ['subject' => 'Nghỉ lễ' . ($holiday ? ': ' . $holiday : '')] + $common];
        }

        if ($base->assignment_type === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY
            || $group?->assignment_type === ScheduleSlotGroup::ASSIGNMENT_TYPE_SELF_STUDY) {
            return [['kind' => 'self_study'] + ['lessonType' => 'Tự học'] + $common];
        }

        $baseTeacher = $base->teacher ?? $group?->teacher;
        if ($baseTeacher === null && $base->scheduleSlotSubgroups->isEmpty()) {
            // Chua phan cong: chi hien thi ten mon.
            return [['kind' => 'unassigned'] + $common];
        }

        $baseRoom = $base->room ?? $group?->room;
        $lessonType = (string) ($base->lesson_type ?: $group?->lesson_type ?: '');
        $assigned = [
            'kind' => 'assigned',
            'lesson' => ScheduleSlotLabels::lessonText($base),
        ] + $common;

        if ($base->scheduleSlotSubgroups->isEmpty()) {
            return [array_merge($assigned, [
                'teacher' => (string) ($baseTeacher?->name ?? ''),
                'room' => ScheduleSlotLabels::roomLabel($baseRoom),
                'lessonType' => self::LESSON_TYPE_LABELS[$lessonType] ?? '',
            ])];
        }

        // Tiet tach to thuc hanh: moi to mot muc.
        $items = [];
        foreach ($base->scheduleSlotSubgroups->sortBy(['group_label', 'id'])->values() as $subgroup) {
            $items[] = array_merge($assigned, [
                'teacher' => (string) (($subgroup->teacher ?? $baseTeacher)?->name ?? ''),
                'room' => ScheduleSlotLabels::roomLabel($subgroup->room ?? $baseRoom),
                'lessonType' => self::LESSON_TYPE_LABELS[ScheduleSlot::LESSON_TYPE_PRACTICE],
                'note' => (string) $subgroup->group_label,
            ]);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $common
     * @return array<string, mixed>
     */
    private function eventItem(ScheduleSlot $slot, array $common): array
    {
        $eventType = (string) ($slot->event_type ?: $slot->semesterEvent?->event_type ?: 'other');
        $title = trim((string) ($slot->semesterEvent?->title ?: $slot->subject ?: ''));

        return array_merge($common, [
            'kind' => $eventType === 'holiday' ? 'holiday' : 'event',
            'subject' => $title !== '' ? $title : (self::EVENT_TYPE_LABELS[$eventType] ?? 'Sự kiện'),
            'lessonType' => self::EVENT_TYPE_LABELS[$eventType] ?? 'Sự kiện',
            'note' => trim((string) ($slot->content ?? '')),
        ]);
    }
}
