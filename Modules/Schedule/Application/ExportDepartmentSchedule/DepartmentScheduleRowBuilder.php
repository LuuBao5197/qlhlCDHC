<?php

namespace Modules\Schedule\Application\ExportDepartmentSchedule;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;

/**
 * Chuyen cac ScheduleSlot (moi slot = 1 tiet) thanh cac dong cua mau bao cao:
 * cac tiet lien tiep cung ngay/lop/mon/giao vien/phong duoc gop thanh mot dong.
 */
class DepartmentScheduleRowBuilder
{
    /**
     * @param Collection<int, ScheduleSlot> $slots Da eager load: trainingClass, teacher, subjectModel,
     *        subjectLesson, room, scheduleSlotGroup(.teacher,.room), scheduleSlotSubgroups(.teacher,.room)
     * @param array<int, array<int, int>> $priorUsage [class_id => [subject_lesson_id => so tiet da hoc truoc ky bao cao]]
     * @return array<int, DepartmentScheduleRow>
     */
    public function build(Collection $slots, array $priorUsage = []): array
    {
        $units = $this->buildUnits($slots);
        $ordinals = $this->buildLessonOrdinals($units);

        $entries = [];
        foreach ($units as $unit) {
            foreach ($this->expandUnit($unit) as $entry) {
                $entries[] = $entry;
            }
        }

        $rows = [];
        foreach ($this->groupIntoBlocks($entries) as $block) {
            $rows[] = $this->makeRow($block, $ordinals, $priorUsage);
        }

        usort($rows, static function (DepartmentScheduleRow $a, DepartmentScheduleRow $b): int {
            return [$a->date->timestamp, $a->fromPeriod, $a->classes, $a->note, $a->teacher]
                <=> [$b->date->timestamp, $b->fromPeriod, $b->classes, $b->note, $b->teacher];
        });

        return $rows;
    }

    /**
     * Mot "unit" = cac slot cung ngay, cung tiet, cung nhom ghep (hoac 1 slot le).
     *
     * @return array<string, array{date: CarbonImmutable, period: int, slots: array<int, ScheduleSlot>}>
     */
    private function buildUnits(Collection $slots): array
    {
        $units = [];
        foreach ($slots as $slot) {
            if (! $this->isReportable($slot)) {
                continue;
            }

            $date = CarbonImmutable::parse($slot->date)->startOfDay();
            $period = (int) $slot->period_number;
            $groupKey = $slot->schedule_slot_group_id !== null ? 'g' . $slot->schedule_slot_group_id : 's' . $slot->id;
            $key = $date->format('Y-m-d') . '|' . $period . '|' . $groupKey;

            $units[$key] ??= ['date' => $date, 'period' => $period, 'slots' => []];
            $units[$key]['slots'][] = $slot;
        }

        foreach ($units as &$unit) {
            usort($unit['slots'], fn (ScheduleSlot $a, ScheduleSlot $b) => strcmp($this->classLabel($a), $this->classLabel($b)) ?: $a->id <=> $b->id);
        }
        unset($unit);

        return $units;
    }

    private function isReportable(ScheduleSlot $slot): bool
    {
        if ($slot->slot_type !== 'subject' || $slot->slot_status === 'cancelled') {
            return false;
        }

        if ($slot->date === null || $slot->period_number === null) {
            return false;
        }

        return $slot->assignment_type !== ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY
            && $slot->scheduleSlotGroup?->assignment_type !== ScheduleSlotGroup::ASSIGNMENT_TYPE_SELF_STUDY;
    }

    /**
     * Thu tu (theo thoi gian) cua tung tiet trong cung mot cap (lop, bai hoc), dung de danh dau "(tiếp)".
     *
     * @return array<string, array<string, int>> ["classId|lessonId" => ["Y-m-d|period" => thu_tu]]
     */
    private function buildLessonOrdinals(array $units): array
    {
        $periodsByPair = [];
        foreach ($units as $unit) {
            $slot = $unit['slots'][0];
            if ($slot->subject_lesson_id === null) {
                continue;
            }

            $pair = (int) $slot->class_id . '|' . (int) $slot->subject_lesson_id;
            $periodsByPair[$pair][$unit['date']->format('Y-m-d') . '|' . $unit['period']] = true;
        }

        $ordinals = [];
        foreach ($periodsByPair as $pair => $periods) {
            $sorted = array_keys($periods);
            sort($sorted);
            $ordinals[$pair] = array_flip($sorted);
        }

        return $ordinals;
    }

    /**
     * Moi unit thanh 1 entry, hoac N entry neu slot duoc tach to thuc hanh.
     *
     * @return array<int, array<string, mixed>>
     */
    private function expandUnit(array $unit): array
    {
        /** @var array<int, ScheduleSlot> $unitSlots */
        $unitSlots = $unit['slots'];
        $base = $unitSlots[0];
        $group = $base->scheduleSlotGroup;

        $classIds = array_map(static fn (ScheduleSlot $s) => (int) $s->class_id, $unitSlots);
        $classes = implode(', ', array_values(array_unique(array_map(fn (ScheduleSlot $s) => $this->classLabel($s), $unitSlots))));

        $subgroups = collect();
        foreach ($unitSlots as $slot) {
            if ($slot->scheduleSlotSubgroups->isNotEmpty()) {
                $subgroups = $slot->scheduleSlotSubgroups->sortBy(['group_label', 'id'])->values();
                break;
            }
        }

        $common = [
            'date' => $unit['date'],
            'period' => $unit['period'],
            'classIds' => $classIds,
            'classes' => $classes,
            'subjectId' => (int) $base->subject_id,
            'subject' => (string) ($base->subjectModel?->name ?? $base->subject ?? ''),
            'lessonId' => $base->subject_lesson_id !== null ? (int) $base->subject_lesson_id : null,
            'lessonText' => $this->lessonText($base),
        ];

        $baseTeacher = $base->teacher ?? $group?->teacher;
        $baseRoom = $base->room ?? $group?->room;

        if ($subgroups->isEmpty()) {
            return [$common + [
                'note' => '',
                'teacher' => (string) ($baseTeacher?->name ?? ''),
                'room' => $this->roomLabel($baseRoom),
            ]];
        }

        $entries = [];
        foreach ($subgroups as $subgroup) {
            $entries[] = $common + [
                'note' => (string) $subgroup->group_label,
                'teacher' => (string) (($subgroup->teacher ?? $baseTeacher)?->name ?? ''),
                'room' => $this->roomLabel($subgroup->room ?? $baseRoom),
            ];
        }

        return $entries;
    }

    /**
     * Gop cac tiet lien tiep (n, n+1, ...) cua cung ngay/lop/mon/giao vien/phong/to thanh mot khoi.
     *
     * @param array<int, array<string, mixed>> $entries
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupIntoBlocks(array $entries): array
    {
        $buckets = [];
        foreach ($entries as $entry) {
            $key = implode('|', [
                $entry['date']->format('Y-m-d'),
                implode(',', $entry['classIds']),
                $entry['subjectId'],
                $entry['teacher'],
                $entry['room'],
                $entry['note'],
            ]);
            $buckets[$key][] = $entry;
        }

        $blocks = [];
        foreach ($buckets as $bucket) {
            usort($bucket, static fn (array $a, array $b) => $a['period'] <=> $b['period']);

            $current = [];
            foreach ($bucket as $entry) {
                $last = $current === [] ? null : $current[array_key_last($current)]['period'];
                if ($last !== null && $entry['period'] !== $last + 1) {
                    $blocks[] = $current;
                    $current = [];
                }
                $current[] = $entry;
            }
            if ($current !== []) {
                $blocks[] = $current;
            }
        }

        return $blocks;
    }

    /**
     * @param array<int, array<string, mixed>> $block
     * @param array<string, array<string, int>> $ordinals
     * @param array<int, array<int, int>> $priorUsage
     */
    private function makeRow(array $block, array $ordinals, array $priorUsage): DepartmentScheduleRow
    {
        $first = $block[0];
        $last = $block[array_key_last($block)];

        $parts = [];
        foreach ($block as $entry) {
            $partKey = (string) ($entry['lessonId'] ?? 'none:' . $entry['lessonText']);
            if (! isset($parts[$partKey])) {
                $parts[$partKey] = ['entry' => $entry, 'count' => 0];
            }
            $parts[$partKey]['count']++;
        }

        $lessonText = [];
        foreach ($parts as $part) {
            $lessonText[] = $this->formatLessonPart($part['entry'], $part['count'], $ordinals, $priorUsage);
        }

        return new DepartmentScheduleRow(
            date: $first['date'],
            classes: $first['classes'],
            fromPeriod: $first['period'],
            toPeriod: $last['period'],
            room: $first['room'],
            subject: $first['subject'],
            lesson: implode('. ', array_filter($lessonText, static fn (string $text) => $text !== '')),
            teacher: $first['teacher'],
            note: $first['note'],
        );
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function formatLessonPart(array $entry, int $count, array $ordinals, array $priorUsage): string
    {
        if ($entry['lessonId'] === null || $entry['lessonText'] === '') {
            return $entry['lessonText'];
        }

        $classId = $entry['classIds'][0];
        $pair = $classId . '|' . $entry['lessonId'];
        $ordinal = $ordinals[$pair][$entry['date']->format('Y-m-d') . '|' . $entry['period']] ?? 0;
        $isContinuation = ((int) ($priorUsage[$classId][$entry['lessonId']] ?? 0)) + $ordinal > 0;

        return $entry['lessonText'] . ($isContinuation ? ' (tiếp)' : '') . ' (' . $count . ')';
    }

    private function lessonText(ScheduleSlot $slot): string
    {
        $lesson = $slot->subjectLesson;
        if ($lesson === null) {
            return trim((string) ($slot->content ?? ''));
        }

        $title = trim((string) $lesson->title);
        if ($title === '') {
            return 'Bài ' . $lesson->lesson_no;
        }

        // Tieu de da co san tien to dang "Bài 3:", "Unit 1:" thi khong them "Bài n:" nua.
        if (preg_match('/^\p{L}+\s*\d+\s*[:.\-–]/u', $title) === 1) {
            return $title;
        }

        return 'Bài ' . $lesson->lesson_no . ': ' . $title;
    }

    private function classLabel(ScheduleSlot $slot): string
    {
        $class = $slot->trainingClass;

        return (string) ($class?->code ?: $class?->name ?: '');
    }

    private function roomLabel(mixed $room): string
    {
        return $room === null ? '' : (string) ($room->code ?: $room->name ?: '');
    }
}
