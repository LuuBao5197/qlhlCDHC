<?php

namespace Modules\Schedule\Application\Shared;

use Carbon\Carbon;

class ScheduleSlotConflictChecker
{
    /**
     * Every class in $classIds must be covered, month by month, by at least one
     * plan_template or semester_event whose date range overlaps that month.
     *
     * The check is clamped to each class's own actual data span (the earliest
     * start / latest end among its own templates and class-specific events),
     * intersected with the plan range. This avoids false gaps at the plan's
     * leading/trailing edge when effective_from/effective_to spills a day or
     * two into a month that no rule/event was ever meant to cover (e.g. a
     * semester end date that lands on the 1st of the following month). A
     * class with no data at all still has its whole plan range flagged, so a
     * fully-forgotten class is still caught.
     *
     * @param array<int, array{class_id:int, start_date:string, end_date:string}> $templates
     * @param array<int, array{class_id:?int, start_date:string, end_date:string}> $events
     * @param array<int, int> $classIds
     * @return array<int, array{class_id:int, month:int, year:int}>
     */
    public function findMonthsWithoutCoverage(
        array $templates,
        array $events,
        array $classIds,
        Carbon $rangeStart,
        Carbon $rangeEnd
    ): array {
        $gaps = [];

        foreach ($classIds as $classId) {
            $classId = (int) $classId;
            [$classDataStart, $classDataEnd] = $this->classDataRange($classId, $templates, $events);

            $checkStart = $classDataStart !== null ? $classDataStart->copy()->max($rangeStart) : $rangeStart->copy();
            $checkEnd = $classDataEnd !== null ? $classDataEnd->copy()->min($rangeEnd) : $rangeEnd->copy();

            if ($checkStart->gt($checkEnd)) {
                continue;
            }

            $cursor = $checkStart->copy()->startOfMonth();
            $lastMonth = $checkEnd->copy()->startOfMonth();

            while ($cursor->lte($lastMonth)) {
                $monthStart = $cursor->copy()->startOfMonth()->max($checkStart);
                $monthEnd = $cursor->copy()->endOfMonth()->min($checkEnd);

                if (! $this->classCoveredInRange($classId, $templates, $events, $monthStart, $monthEnd)) {
                    $gaps[] = [
                        'class_id' => $classId,
                        'month' => $cursor->month,
                        'year' => $cursor->year,
                    ];
                }

                $cursor->addMonth();
            }
        }

        return $gaps;
    }

    /**
     * The earliest start / latest end among a class's own templates and
     * class-specific events (global events with class_id = null are excluded,
     * since they don't indicate when the class's own schedule actually runs).
     *
     * @param array<int, array{class_id:int, start_date:string, end_date:string}> $templates
     * @param array<int, array{class_id:?int, start_date:string, end_date:string}> $events
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function classDataRange(int $classId, array $templates, array $events): array
    {
        $minStart = null;
        $maxEnd = null;

        foreach ($templates as $template) {
            if ((int) $template['class_id'] !== $classId) {
                continue;
            }

            $start = Carbon::parse($template['start_date'])->startOfDay();
            $end = Carbon::parse($template['end_date'])->endOfDay();

            $minStart = $minStart === null || $start->lt($minStart) ? $start : $minStart;
            $maxEnd = $maxEnd === null || $end->gt($maxEnd) ? $end : $maxEnd;
        }

        foreach ($events as $event) {
            if (($event['class_id'] ?? null) === null || (int) $event['class_id'] !== $classId) {
                continue;
            }

            $start = Carbon::parse($event['start_date'])->startOfDay();
            $end = Carbon::parse($event['end_date'])->endOfDay();

            $minStart = $minStart === null || $start->lt($minStart) ? $start : $minStart;
            $maxEnd = $maxEnd === null || $end->gt($maxEnd) ? $end : $maxEnd;
        }

        return [$minStart, $maxEnd];
    }

    /**
     * Finds plan_template rows and semester_event rows that would collide on the
     * same class/date/period once schedule_slot rows are generated for them.
     *
     * 'holiday' events are excluded from this check: they represent a global or
     * class-wide suspension of teaching that overrides any template occurrence
     * landing on the same dates (the slot-generation step cancels those slots),
     * so they are not a genuine authoring conflict and should not force a rule
     * to be split around the holiday. 'review'/'exam'/'other' events remain a
     * hard conflict since they occupy a specific period for a specific
     * activity and overlapping a subject rule there would be ambiguous.
     *
     * @param array<int, array{class_id:int, start_date:string, end_date:string, days_of_week:array<int,int>, period_range:string, subject_label?:string}> $templates
     * @param array<int, array{class_id:?int, start_date:string, end_date:string, period_from:int, period_to:int, title?:?string, event_type?:?string}> $events
     * @param array<int, int> $classIds used to expand events with class_id = null (applies to every class)
     * @param array<int, string> $classCodes class_id => class code, for readable messages
     * @return array<int, string>
     */
    public function findTemplateEventConflicts(array $templates, array $events, array $classIds, array $classCodes = []): array
    {
        $templatesByClass = collect($templates)->groupBy(fn (array $template) => (int) $template['class_id']);
        $conflicts = [];

        foreach ($events as $event) {
            if (($event['event_type'] ?? null) === 'holiday') {
                continue;
            }

            $eventClassIds = $event['class_id'] !== null ? [(int) $event['class_id']] : array_map('intval', $classIds);
            $eventStart = Carbon::parse($event['start_date'])->startOfDay();
            $eventEnd = Carbon::parse($event['end_date'])->endOfDay();
            $eventPeriods = range((int) ($event['period_from'] ?? 1), (int) ($event['period_to'] ?? 9));

            foreach ($eventClassIds as $classId) {
                foreach ($templatesByClass->get($classId, collect()) as $template) {
                    $templateStart = Carbon::parse($template['start_date'])->startOfDay();
                    $templateEnd = Carbon::parse($template['end_date'])->endOfDay();

                    if ($eventEnd->lt($templateStart) || $eventStart->gt($templateEnd)) {
                        continue;
                    }

                    $templatePeriods = $this->parsePeriodRange((string) $template['period_range']);
                    if ($templatePeriods === [] || array_intersect($eventPeriods, $templatePeriods) === []) {
                        continue;
                    }

                    $overlapStart = $eventStart->copy()->max($templateStart);
                    $overlapEnd = $eventEnd->copy()->min($templateEnd);
                    $daysOfWeek = $template['days_of_week'];

                    for ($date = $overlapStart->copy(); $date->lte($overlapEnd); $date->addDay()) {
                        if (in_array($date->dayOfWeekIso + 1, $daysOfWeek, true)) {
                            $conflicts[] = sprintf(
                                "Lop '%s' bi trung lich ngay %s (tiet %s): su kien '%s' trung voi mon '%s'.",
                                $classCodes[$classId] ?? ('#' . $classId),
                                $date->toDateString(),
                                (string) $template['period_range'],
                                $event['title'] ?? ($event['event_type'] ?? 'su kien'),
                                $template['subject_label'] ?? ($template['source'] ?? 'mon hoc')
                            );

                            break;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($conflicts));
    }

    private function classCoveredInRange(int $classId, array $templates, array $events, Carbon $monthStart, Carbon $monthEnd): bool
    {
        foreach ($templates as $template) {
            if ((int) $template['class_id'] !== $classId) {
                continue;
            }

            $templateStart = Carbon::parse($template['start_date'])->startOfDay();
            $templateEnd = Carbon::parse($template['end_date'])->endOfDay();

            if ($templateStart->lte($monthEnd) && $templateEnd->gte($monthStart)) {
                return true;
            }
        }

        foreach ($events as $event) {
            $eventClassId = $event['class_id'] !== null ? (int) $event['class_id'] : null;
            if ($eventClassId !== null && $eventClassId !== $classId) {
                continue;
            }

            $eventStart = Carbon::parse($event['start_date'])->startOfDay();
            $eventEnd = Carbon::parse($event['end_date'])->endOfDay();

            if ($eventStart->lte($monthEnd) && $eventEnd->gte($monthStart)) {
                return true;
            }
        }

        return false;
    }

    private function parsePeriodRange(string $range): array
    {
        if (preg_match('/^\s*(\d+)\s*(?:-\s*(\d+)\s*)?$/', $range, $matches)) {
            $start = (int) $matches[1];
            $end = isset($matches[2]) ? (int) $matches[2] : $start;

            if ($start >= 1 && $end >= $start) {
                return range($start, $end);
            }
        }

        return [];
    }
}
