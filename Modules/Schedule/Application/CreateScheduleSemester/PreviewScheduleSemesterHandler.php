<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use Carbon\Carbon;

/**
 * Dry-run projection of the class-tab rules / semester events currently held
 * in the create/edit form (client side, not yet submitted) into an
 * Excel-style weekly grid. Does not touch the database: no plan_templates,
 * schedule_slots, or semester_events are read or written here.
 */
class PreviewScheduleSemesterHandler
{
    public function __construct(
        private readonly PreviewImportFileParser $importFileParser = new PreviewImportFileParser()
    ) {}

    public function handle(PreviewScheduleSemesterRequest $request): array
    {
        $validated = $request->validated();

        $planStart = Carbon::parse($validated['start_date'])->startOfDay();
        $planEnd = Carbon::parse($validated['end_date'])->endOfDay();

        $rules = $this->normalizeRules($this->decodeArray($validated['rules'] ?? null));
        $events = array_merge(
            $this->normalizeEvents($this->decodeArray($validated['class_events'] ?? null)),
            $this->normalizeEvents($this->decodeArray($validated['global_events'] ?? null))
        );

        $warnings = [];

        if ($request->hasFile('import_file')) {
            $imported = $this->importFileParser->parse(
                $request->file('import_file'),
                $validated['class_code'] ?? null,
                $planStart,
                $planEnd
            );

            $rules = array_merge($rules, $imported['rules']);
            $events = array_merge($events, $imported['events']);
            $warnings = $imported['warnings'];
        }

        $weeks = $this->buildWeeks($planStart, $planEnd);

        return [
            'weeks' => $weeks,
            'rows' => $this->buildRows($weeks, $rules, $events),
            'warnings' => $warnings,
        ];
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function normalizeRules(array $rawRules): array
    {
        $rules = [];

        foreach ($rawRules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $subject = trim((string) ($rule['subject'] ?? ''));
            $weekdays = $this->normalizeWeekdays($rule['weekdays'] ?? []);
            $periodFrom = $this->toPeriod($rule['period_from'] ?? null);
            $periodTo = $this->toPeriod($rule['period_to'] ?? null);
            $startDate = $rule['start_date'] ?? null;
            $endDate = $rule['end_date'] ?? null;

            if ($subject === ''
                || $weekdays === []
                || $periodFrom === null
                || $periodTo === null
                || $periodTo < $periodFrom
                || !$this->isDateValue($startDate)
                || !$this->isDateValue($endDate)
            ) {
                continue;
            }

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            if ($end->lt($start)) {
                continue;
            }

            $rules[] = [
                'subject' => $subject,
                'weekdays' => $weekdays,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'start' => $start,
                'end' => $end,
            ];
        }

        return $rules;
    }

    private function normalizeEvents(array $rawEvents): array
    {
        $events = [];

        foreach ($rawEvents as $event) {
            if (!is_array($event)) {
                continue;
            }

            $title = trim((string) ($event['title'] ?? ''));
            $startDate = $event['start_date'] ?? null;
            $endDate = $event['end_date'] ?? null;

            if ($title === '' || !$this->isDateValue($startDate) || !$this->isDateValue($endDate)) {
                continue;
            }

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            if ($end->lt($start)) {
                continue;
            }

            $periodFrom = $this->toPeriod($event['period_from'] ?? null) ?? 1;
            $periodTo = $this->toPeriod($event['period_to'] ?? null) ?? 9;

            if ($periodTo < $periodFrom) {
                $periodFrom = 1;
                $periodTo = 9;
            }

            $events[] = [
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
            ];
        }

        return $events;
    }

    /**
     * @return array<int, array{index:int, start_date:string}>
     */
    private function buildWeeks(Carbon $planStart, Carbon $planEnd): array
    {
        $weeks = [];
        $cursor = $planStart->copy()->startOfWeek(Carbon::MONDAY);
        $index = 1;

        while ($cursor->lte($planEnd)) {
            $weeks[] = [
                'index' => $index,
                'start_date' => $cursor->toDateString(),
            ];

            $index++;
            $cursor->addWeek();
        }

        return $weeks;
    }

    private function buildRows(array $weeks, array $rules, array $events): array
    {
        $rows = [];

        foreach (range(2, 8) as $dayOfWeek) {
            $bands = $this->resolvePeriodBands($dayOfWeek, $rules, $events);

            foreach ($bands as [$periodFrom, $periodTo]) {
                $cells = [];
                $hasCoverage = false;

                foreach ($weeks as $week) {
                    $date = Carbon::parse($week['start_date'])->addDays($dayOfWeek - 2);
                    $label = $this->resolveLabel($date, $dayOfWeek, $periodFrom, $periodTo, $rules, $events);

                    if ($label !== null) {
                        $hasCoverage = true;
                    }

                    $cells[] = [
                        'week_index' => $week['index'],
                        'label' => $label,
                    ];
                }

                // A band with no rule/event covering it on any week is just an
                // artifact of splitting the period range at other rules'
                // boundaries (e.g. a gap between periods 5 and 7) — skip it
                // instead of rendering an always-empty row.
                if (!$hasCoverage) {
                    continue;
                }

                $rows[] = [
                    'day_of_week' => $dayOfWeek,
                    'period_from' => $periodFrom,
                    'period_to' => $periodTo,
                    'cells' => $this->mergeCells($cells),
                ];
            }
        }

        return $rows;
    }

    /**
     * Splits the 1-9 period range into the smallest set of bands that keep
     * every rule/event boundary for this weekday intact, so two rules that
     * sit back-to-back within the same loose "session" (e.g. periods 1-3 and
     * 4-5) land on separate rows instead of being collapsed into one.
     *
     * @return array<int, array{0:int, 1:int}>
     */
    private function resolvePeriodBands(int $dayOfWeek, array $rules, array $events): array
    {
        $breakpoints = [];

        foreach ($rules as $rule) {
            if (!in_array($dayOfWeek, $rule['weekdays'], true)) {
                continue;
            }

            $breakpoints[] = $rule['period_from'];
            $breakpoints[] = $rule['period_to'] + 1;
        }

        foreach ($events as $event) {
            $breakpoints[] = $event['period_from'];
            $breakpoints[] = $event['period_to'] + 1;
        }

        $breakpoints = collect($breakpoints)
            ->filter(fn ($point) => $point >= 1 && $point <= 10)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $bands = [];
        for ($i = 0; $i < count($breakpoints) - 1; $i++) {
            $from = $breakpoints[$i];
            $to = $breakpoints[$i + 1] - 1;

            if ($to >= $from) {
                $bands[] = [$from, $to];
            }
        }

        return $bands;
    }

    private function resolveLabel(
        Carbon $date,
        int $dayOfWeek,
        int $periodFrom,
        int $periodTo,
        array $rules,
        array $events
    ): ?string {
        // Events take priority over rules when they overlap the same day/period.
        foreach ($events as $event) {
            if ($date->lt($event['start']) || $date->gt($event['end'])) {
                continue;
            }

            if (max($periodFrom, $event['period_from']) > min($periodTo, $event['period_to'])) {
                continue;
            }

            return $event['title'];
        }

        foreach ($rules as $rule) {
            if (max($periodFrom, $rule['period_from']) > min($periodTo, $rule['period_to'])) {
                continue;
            }

            if ($date->lt($rule['start']) || $date->gt($rule['end'])) {
                continue;
            }

            if (!in_array($dayOfWeek, $rule['weekdays'], true)) {
                continue;
            }

            return $rule['subject'];
        }

        return null;
    }

    /**
     * Merges consecutive weeks in a row that share the same label (including
     * empty gaps) into a single cell with a colspan, mirroring how the Excel
     * sample table draws a subject as one wide block across several weeks.
     */
    private function mergeCells(array $cells): array
    {
        $merged = [];

        foreach ($cells as $cell) {
            $lastIndex = count($merged) - 1;

            if ($lastIndex >= 0 && $merged[$lastIndex]['label'] === $cell['label']) {
                $merged[$lastIndex]['colspan']++;
                continue;
            }

            $merged[] = [
                'week_index' => $cell['week_index'],
                'label' => $cell['label'],
                'colspan' => 1,
            ];
        }

        return $merged;
    }

    private function normalizeWeekdays(mixed $value): array
    {
        $weekdays = is_array($value) ? $value : [];

        return collect($weekdays)
            ->filter(fn ($item) => is_numeric($item))
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $day) => $day >= 2 && $day <= 8)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function toPeriod(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $period = (int) $value;

        return $period >= 1 && $period <= 9 ? $period : null;
    }

    private function isDateValue(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            Carbon::parse($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
