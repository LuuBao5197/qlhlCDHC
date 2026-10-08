<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use Illuminate\Support\Collection;
use Modules\Schedule\Models\SemesterEvent;

/**
 * Converts stored plan templates/events back into the form payload shape
 * (class_tab_rules / class_semester_events / global_semester_events).
 */
class PlanRuleFormConverter
{
    /**
     * @return array<string, array<int, array<string, mixed>>> class id => rules
     */
    public function templatesToRules(Collection $templates): array
    {
        $rulesByClass = [];

        foreach ($templates as $template) {
            $classId = (string) $template->class_id;

            $weekdaysRaw = $template->days_of_week;

            // days_of_week may be an array cast, or a JSON string from legacy data.
            if (is_string($weekdaysRaw)) {
                $decoded = json_decode($weekdaysRaw, true);
                $weekdaysRaw = is_array($decoded) ? $decoded : [];
            }

            if (!is_array($weekdaysRaw)) {
                $weekdaysRaw = [];
            }

            $weekdays = collect($weekdaysRaw)
                ->filter(fn ($day) => is_numeric($day))
                ->map(fn ($day) => (int) $day)
                ->filter(fn (int $day) => $day >= 2 && $day <= 8)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($weekdays === [] && is_numeric($template->day_of_week)) {
                $dow = (int) $template->day_of_week;
                if ($dow >= 2 && $dow <= 8) {
                    $weekdays = [$dow];
                }
            }

            $rulesByClass[$classId][] = [
                'start_date' => $template->start_date->toDateString(),
                'end_date' => $template->end_date->toDateString(),
                'period_from' => $this->periodPart($template->period_range, 1),
                'period_to' => $this->periodPart($template->period_range, 2),
                'subject' => $template->subjects?->code ?? '',
                'content' => $template->description ?? '',
                'weekdays' => $weekdays,
            ];
        }

        return $rulesByClass;
    }

    public function globalEventsToForm(Collection $events): array
    {
        return $events->map(fn (SemesterEvent $event) => $this->eventToForm($event))->values()->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>> class id => events
     */
    public function classEventsToForm(Collection $events): array
    {
        return $events
            ->groupBy(fn (SemesterEvent $event) => (string) $event->class_id)
            ->map(fn ($group) => $group->map(fn (SemesterEvent $event) => $this->eventToForm($event))->values()->all())
            ->all();
    }

    private function eventToForm(SemesterEvent $event): array
    {
        return [
            'event_type' => $event->event_type,
            'title' => $event->title,
            'start_date' => optional($event->start_date)->toDateString() ?? '',
            'end_date' => optional($event->end_date)->toDateString() ?? '',
            'period_from' => $event->period_from,
            'period_to' => $event->period_to,
            'note' => $event->note,
            'sort_order' => $event->sort_order,
        ];
    }

    private function periodPart(mixed $periodRange, int $group): ?int
    {
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', trim((string) $periodRange), $matches)) {
            return (int) $matches[$group];
        }

        return null;
    }
}
