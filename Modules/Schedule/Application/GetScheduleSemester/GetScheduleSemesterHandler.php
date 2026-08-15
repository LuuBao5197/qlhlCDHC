<?php

namespace Modules\Schedule\Application\GetScheduleSemester;

use Carbon\Carbon;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\SemesterEvent;

class GetScheduleSemesterHandler
{
    public function handle(GetScheduleSemesterRequest $request)
    {
        $validated = $request->validated();
        $semester = $validated['semester'] ?? null;
        $year = $validated['year'] ?? null;
        $trainingBatchId = $validated['training_batch_id'] ?? null;
        $className = $validated['className'] ?? null;

        if (!$semester || !$year) {
            $planOptions = Plans::query()
                ->with('trainingBatch')
                ->orderByDesc('year')
                ->orderByDesc('semester')
                ->get(['id', 'semester', 'year', 'training_batch_id']);

            return view('schedule::semester', ['plan' => null, 'classOptions' => [], 'planOptions' => $planOptions]);
        }

        $matchingPlans = Plans::query()
            ->with('trainingBatch')
            ->where('semester', $semester)
            ->where('year', $year)
            ->when($trainingBatchId, fn ($query) => $query->where('training_batch_id', (int) $trainingBatchId))
            ->latest()
            ->get();

        if ($matchingPlans->isEmpty()) {
            return view('schedule::semester', ['plan' => null, 'classOptions' => []]);
        }

        // Multiple plans share this semester/year across different training batches (khóa học) —
        // ask the user to disambiguate instead of silently guessing one.
        if (!$trainingBatchId && $matchingPlans->count() > 1) {
            return view('schedule::semester', [
                'plan' => null,
                'classOptions' => [],
                'planOptions' => $matchingPlans,
                'ambiguous' => true,
            ]);
        }

        $plan = $matchingPlans->first();

        $classOptions = PlanTemplates::query()
            ->with('trainingClass')
            ->where('plan_id', $plan->id)
            ->get()
            ->pluck('trainingClass')
            ->filter()
            ->unique('id')
            ->sortBy('code')
            ->values();

        $planTemplates = PlanTemplates::query()
            ->with(['subjects', 'trainingClass'])
            ->where('plan_id', $plan->id)
            ->when($className, function ($query) use ($className) {
                $query->whereHas('trainingClass', fn ($classQuery) => $classQuery->where('code', $className));
            })
            ->orderBy('id')
            ->get();

        $semesterEvents = SemesterEvent::query()
            ->with(['trainingClass'])
            ->where('plan_id', $plan->id)
            ->when($className, function ($query) use ($className) {
                $query->where(function ($eventQuery) use ($className) {
                    $eventQuery
                        ->whereNull('class_id')
                        ->orWhereHas('trainingClass', fn ($classQuery) => $classQuery->where('code', $className));
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->sortBy(fn ($event) => $event->class_id === null ? 1 : 0)
            ->values();

        if ($planTemplates->isEmpty() && $semesterEvents->isEmpty()) {
            return view('schedule::semester', [
                'plan' => $plan,
                'renderRows' => [],
                'dates' => [],
                'periods' => range(1, 9),
                'className' => $className,
                'classOptions' => $classOptions,
            ]);
        }

        $rangeStart = Carbon::parse($plan->effective_from ?? $planTemplates->min('start_date') ?? $semesterEvents->min('start_date'))->startOfDay();
        $rangeEnd = Carbon::parse($plan->effective_to ?? $planTemplates->max('end_date') ?? $semesterEvents->max('end_date'))->endOfDay();

        $dates = [];
        for ($cursor = $rangeStart->copy(); $cursor->lte($rangeEnd); $cursor->addDay()) {
            $dates[] = $cursor->copy();
        }

        $periods = range(1, 9);
        $scheduleMatrix = [];

        foreach ($planTemplates as $template) {
            $daysOfWeek = $this->normalizeDaysOfWeek($template->days_of_week ?? [$template->day_of_week]);
            $periodRange = $this->parsePeriodRange((string) ($template->period_range ?: ($template->session === 'Chieu' ? '6-9' : '1-5')));
            $content = [
                'type' => 'subject',
                'key' => 'subject|' . $template->id,
                'label' => $template->subjects?->name ?? 'Mon hoc',
                'description' => $template->description,
                'color' => '#f0f9ff',
                'border_color' => '#2563eb',
            ];

            foreach ($dates as $date) {
                $dow = $date->dayOfWeekIso + 1;
                if ($date->lt($template->start_date) || $date->gt($template->end_date) || !in_array($dow, $daysOfWeek, true)) {
                    continue;
                }

                foreach ($periodRange as $period) {
                    $scheduleMatrix[$period][$date->toDateString()] = $content;
                }
            }
        }

        foreach ($semesterEvents as $event) {
            $periodRange = $this->parseEventPeriodRange($event);
            $color = $event->color ?: $this->semesterEventColor($event->event_type);
            $content = [
                'type' => 'event',
                'key' => 'event|' . $event->id,
                'label' => $event->title,
                'description' => $event->note,
                'event_type' => $event->event_type,
                'color' => $color,
                'border_color' => $this->semesterEventBorder($event->event_type, $color),
            ];

            for ($date = Carbon::parse($event->start_date)->startOfDay(); $date->lte(Carbon::parse($event->end_date)->endOfDay()); $date->addDay()) {
                if ($className && $event->class_id && optional($event->trainingClass)->code !== $className) {
                    continue;
                }

                foreach ($periodRange as $period) {
                    $scheduleMatrix[$period][$date->toDateString()] = $content;
                }
            }
        }

        $renderRows = [];
        $assigned = [];

        $isSameContent = function (array|null $first, array|null $second): bool {
            if (!$first || !$second) {
                return false;
            }

            return $first['type'] === $second['type']
                && $first['key'] === $second['key']
                && ($first['label'] ?? null) === ($second['label'] ?? null)
                && ($first['description'] ?? null) === ($second['description'] ?? null)
                && ($first['color'] ?? null) === ($second['color'] ?? null);
        };

        foreach ($periods as $period) {
            foreach ($dates as $index => $date) {
                $dateKey = $date->toDateString();

                if (isset($assigned[$period][$dateKey])) {
                    $renderRows[$period][$dateKey] = ['type' => 'hidden'];
                    continue;
                }

                $current = $scheduleMatrix[$period][$dateKey] ?? null;

                if (!$current) {
                    $renderRows[$period][$dateKey] = ['type' => 'empty', 'colspan' => 1, 'rowspan' => 1];
                    continue;
                }

                $rowspan = 1;
                for ($rp = $period + 1; $rp <= max($periods); $rp++) {
                    if (isset($scheduleMatrix[$rp][$dateKey]) && $isSameContent($scheduleMatrix[$rp][$dateKey], $current)) {
                        $rowspan++;
                    } else {
                        break;
                    }
                }

                $colspan = 1;
                for ($di = $index + 1; $di < count($dates); $di++) {
                    $nextDateKey = $dates[$di]->toDateString();
                    $canMerge = true;

                    for ($cp = $period; $cp < $period + $rowspan; $cp++) {
                        if (!isset($scheduleMatrix[$cp][$nextDateKey]) || !$isSameContent($scheduleMatrix[$cp][$nextDateKey], $current)) {
                            $canMerge = false;
                            break;
                        }
                    }

                    if ($canMerge) {
                        $colspan++;
                    } else {
                        break;
                    }
                }

                for ($i = 0; $i < $rowspan; $i++) {
                    for ($j = 0; $j < $colspan; $j++) {
                        if ($i === 0 && $j === 0) {
                            continue;
                        }

                        $assigned[$period + $i][$dates[$index + $j]->toDateString()] = true;
                    }
                }

                $renderRows[$period][$dateKey] = [
                    'type' => $current['type'],
                    'colspan' => $colspan,
                    'rowspan' => $rowspan,
                    'data' => $current,
                ];
            }
        }

        return view('schedule::semester', compact('plan', 'renderRows', 'dates', 'periods', 'className', 'classOptions'));
    }

    private function normalizeDaysOfWeek(mixed $value): array
    {
        $days = is_array($value)
            ? $value
            : preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return collect($days)
            ->filter(fn ($item) => is_numeric($item))
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $item) => $item >= 2 && $item <= 8)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function parsePeriodRange(string $range): array
    {
        $range = trim($range);

        if (preg_match('/^(\\d+)\\s*-\\s*(\\d+)$/', $range, $matches)) {
            return range((int) $matches[1], (int) $matches[2]);
        }

        $lower = mb_strtolower($range);
        if (in_array($lower, ['chieu', 'chiều', 'afternoon'], true)) {
            return range(6, 9);
        }

        return range(1, 5);
    }

    private function parseEventPeriodRange(SemesterEvent $event): array
    {
        $periodFrom = is_numeric($event->period_from) ? (int) $event->period_from : null;
        $periodTo = is_numeric($event->period_to) ? (int) $event->period_to : null;

        if ($periodFrom === null && $periodTo === null) {
            return range(1, 9);
        }

        $periodFrom = $periodFrom ?? $periodTo ?? 1;
        $periodTo = $periodTo ?? $periodFrom;

        return $periodTo >= $periodFrom ? range($periodFrom, $periodTo) : range($periodFrom, $periodFrom);
    }

    private function semesterEventColor(string $eventType): string
    {
        return match ($eventType) {
            'holiday' => '#ffedd5',
            'review' => '#dbeafe',
            'exam' => '#fee2e2',
            default => '#ede9fe',
        };
    }

    private function semesterEventBorder(string $eventType, string $color): string
    {
        return match ($eventType) {
            'holiday' => '#fb923c',
            'review' => '#3b82f6',
            'exam' => '#ef4444',
            default => '#8b5cf6',
        };
    }
}
