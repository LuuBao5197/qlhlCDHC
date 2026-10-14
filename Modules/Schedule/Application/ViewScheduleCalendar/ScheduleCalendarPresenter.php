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
    public const SESSION_MORNING = 'morning';

    public const SESSION_AFTERNOON = 'afternoon';

    /** Tiet 1-5 la buoi sang, tu tiet 6 la buoi chieu (khop InitializeMonthlyScheduleHandler). */
    private const LAST_MORNING_PERIOD = 5;

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
     * Moi ngay co:
     *  - cells: tiet => cac muc (dung cho xem theo ngay);
     *  - blocks: cac muc da gop tiet lien tiep cung buoi (dung cho xem theo tuan).
     * "periods" chi gom cac tiet co du lieu; ngay Thu 7/Chu nhat khong co tiet bi an khi xem theo tuan.
     * "matrix": hang = lop, cot = ngay, moi o chia buoi sang/chieu (xem theo tuan nhom theo lop).
     *
     * @param Collection<int, ScheduleSlot> $slots
     * @return array{days: array<int, array<string, mixed>>, periods: array<int, int>, matrix: array<int, array<string, mixed>>, hasItems: bool}
     */
    public function present(Collection $slots, DepartmentScheduleExportPeriod $period): array
    {
        $days = [];
        for ($date = $period->start; $date->lte($period->end); $date = $date->addDay()) {
            $days[$date->format('Y-m-d')] = ['date' => $date, 'cells' => [], 'blocks' => []];
        }

        $entries = [];
        $usedPeriods = [];
        foreach ($this->buildUnits($slots) as $unit) {
            $dayKey = $unit['date']->format('Y-m-d');
            if (! isset($days[$dayKey])) {
                continue;
            }

            foreach ($this->itemsForUnit($unit['slots']) as $item) {
                $days[$dayKey]['cells'][$unit['period']][] = $item;
                $entries[$dayKey][] = ['period' => $unit['period'], 'item' => $item];
            }
            $usedPeriods[$unit['period']] = true;
        }

        foreach ($entries as $dayKey => $dayEntries) {
            $days[$dayKey]['blocks'] = $this->mergeBlocks($dayEntries);
        }

        if ($period->mode === DepartmentScheduleExportPeriod::MODE_WEEK) {
            $days = array_filter(
                $days,
                static fn (array $day): bool => $day['date']->isoWeekday() <= 5 || $day['blocks'] !== []
            );
        }

        $periods = array_keys($usedPeriods);
        sort($periods);

        return [
            'days' => array_values($days),
            'periods' => $periods,
            'matrix' => $this->buildClassMatrix($days),
            'hasItems' => $usedPeriods !== [],
        ];
    }

    /**
     * Gop cac tiet lien tiep cung buoi co noi dung giong het nhau thanh mot khoi "Tiet a-b".
     *
     * @param array<int, array{period: int, item: array<string, mixed>}> $entries
     * @return array<int, array<string, mixed>>
     */
    private function mergeBlocks(array $entries): array
    {
        $buckets = [];
        foreach ($entries as $entry) {
            $buckets[serialize($entry['item'])]['item'] = $entry['item'];
            $buckets[serialize($entry['item'])]['periods'][$entry['period']] = true;
        }

        $blocks = [];
        foreach ($buckets as $bucket) {
            $periods = array_keys($bucket['periods']);
            sort($periods);

            $from = $to = null;
            foreach ($periods as $periodNumber) {
                $continues = $to !== null
                    && $periodNumber === $to + 1
                    && $this->sessionOf($periodNumber) === $this->sessionOf($from);

                if ($continues) {
                    $to = $periodNumber;

                    continue;
                }

                if ($from !== null) {
                    $blocks[] = $this->makeBlock($bucket['item'], $from, $to);
                }
                $from = $to = $periodNumber;
            }
            if ($from !== null) {
                $blocks[] = $this->makeBlock($bucket['item'], $from, $to);
            }
        }

        usort($blocks, static fn (array $a, array $b): int => [$a['from'], $a['classes'], $a['subject']] <=> [$b['from'], $b['classes'], $b['subject']]);

        return $blocks;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function makeBlock(array $item, int $from, int $to): array
    {
        return $item + ['from' => $from, 'to' => $to, 'session' => $this->sessionOf($from)];
    }

    private function sessionOf(int $period): string
    {
        return $period <= self::LAST_MORNING_PERIOD ? self::SESSION_MORNING : self::SESSION_AFTERNOON;
    }

    /**
     * @param array<string, array<string, mixed>> $days
     * @return array<int, array{label: string, cells: array<string, array<string, array<int, array<string, mixed>>>>}>
     */
    private function buildClassMatrix(array $days): array
    {
        $rows = [];
        foreach ($days as $dayKey => $day) {
            foreach ($day['blocks'] as $block) {
                $labels = $block['classList'] !== [] ? $block['classList'] : ['—'];
                foreach ($labels as $label) {
                    $rows[$label][$dayKey][$block['session']][] = $block;
                }
            }
        }

        uksort($rows, static fn (string $a, string $b): int => strnatcasecmp($a, $b));

        $matrix = [];
        foreach ($rows as $label => $cells) {
            $matrix[] = ['label' => (string) $label, 'cells' => $cells];
        }

        return $matrix;
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
        $classList = array_values(array_unique(array_filter(
            array_map(static fn (ScheduleSlot $s) => ScheduleSlotLabels::classLabel($s), $unitSlots)
        )));
        $classes = implode(', ', $classList);

        $common = [
            'classes' => $classes,
            'classList' => $classList,
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
