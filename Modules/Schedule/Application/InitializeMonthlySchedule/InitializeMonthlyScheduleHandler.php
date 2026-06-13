<?php

namespace Modules\Schedule\Application\InitializeMonthlySchedule;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\ScheduleSlot;

class InitializeMonthlyScheduleHandler
{
    public function handle(InitializeMonthlyScheduleRequest $request): array
    {
        $validated = $request->validated();
        $targetMonth = (int) $validated['month'];
        $targetYear = (int) $validated['year'];
        $targetDate = Carbon::create($targetYear, $targetMonth, 1);
        $targetMonthStart = $targetDate->copy()->startOfMonth();
        $targetMonthEnd = $targetDate->copy()->endOfMonth();

        return DB::transaction(function () use ($targetMonth, $targetYear, $targetMonthStart, $targetMonthEnd, $request) {
            $plans = Plans::query()
                ->whereIn('status', ['draft', 'submitted', 'approved'])
                ->whereNotNull('effective_from')
                ->whereNotNull('effective_to')
                ->whereDate('effective_from', '<=', $targetMonthEnd->toDateString())
                ->whereDate('effective_to', '>=', $targetMonthStart->toDateString())
                ->with(['planTemplates.subjects'])
                ->get();

            if ($plans->isEmpty()) {
                throw ValidationException::withMessages([
                    'month' => "Khong co ke hoach hoc ky nao hieu luc trong thang {$targetMonth}/{$targetYear}.",
                ]);
            }

            $preparedPlans = [];
            $planErrors = [];

            foreach ($plans as $plan) {
                $prepared = $this->buildScheduleSlotRows(
                    $plan->planTemplates,
                    $targetMonth,
                    $targetYear
                );

                $planLabel = "Plan #{$plan->id} ({$plan->name})";
                if ($prepared['applicable_templates'] === 0) {
                    $planErrors[] = "{$planLabel}: khong co plan_template hieu luc trong thang.";

                    continue;
                }

                if ($prepared['errors'] !== []) {
                    foreach ($prepared['errors'] as $error) {
                        $planErrors[] = "{$planLabel}: {$error}";
                    }

                    continue;
                }

                if ($prepared['slots'] === []) {
                    $planErrors[] = "{$planLabel}: plan_template khong sinh ra schedule_slot nao trong thang.";

                    continue;
                }

                $preparedPlans[] = [
                    'plan' => $plan,
                    'slots' => $prepared['slots'],
                ];
            }

            if ($planErrors !== []) {
                throw ValidationException::withMessages(['month' => $planErrors]);
            }

            $createdCount = 0;
            $slotCount = 0;
            $monthlyScheduleIds = [];

            foreach ($preparedPlans as $preparedPlan) {
                $plan = $preparedPlan['plan'];

                // One MonthlySchedule per plan+month+year (class distinction is at ScheduleSlot level).
                $timestamp = now();

                $createdCount += DB::table('monthly_schedules')->insertOrIgnore([
                    'plan_id' => $plan->id,
                    'month' => $targetMonth,
                    'year' => $targetYear,
                    'created_by' => $request->user()?->id,
                    'status' => 'draft',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $monthlySchedule = MonthlySchedule::query()
                    ->where('plan_id', $plan->id)
                    ->where('month', $targetMonth)
                    ->where('year', $targetYear)
                    ->firstOrFail();

                $monthlyScheduleIds[] = $monthlySchedule->id;

                $slotCount += $this->upsertScheduleSlots($monthlySchedule, $preparedPlan['slots']);
            }

            return [
                'success' => true,
                'processed_plans' => count($preparedPlans),
                'created_schedules' => $createdCount,
                'created_slots' => $slotCount,
                'monthly_schedule_ids' => $monthlyScheduleIds,
            ];
        });
    }

    private function buildScheduleSlotRows(
        iterable $templates,
        int $targetMonth,
        int $targetYear
    ): array {
        $slotsByKey = [];
        $errors = [];
        $applicableTemplates = 0;

        $monthStart = Carbon::create($targetYear, $targetMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();

        foreach ($templates as $template) {
            if (($template->start_date === null) !== ($template->end_date === null)) {
                $errors[] = "Template #{$template->id} phai co ca start_date va end_date, hoac de trong ca hai.";

                continue;
            }

            $classId = $template->class_id;
            $templateStart = $template->start_date ? Carbon::parse($template->start_date)->startOfDay() : null;
            $templateEnd = $template->end_date ? Carbon::parse($template->end_date)->endOfDay() : null;

            if (($templateStart && $templateStart->gt($monthEnd)) || ($templateEnd && $templateEnd->lt($monthStart))) {
                continue;
            }

            $applicableTemplates++;

            $daysOfWeek = is_array($template->days_of_week)
                ? $template->days_of_week
                : (json_decode($template->days_of_week, true) ?: [$template->day_of_week]);

            $daysOfWeek = $this->normalizeTemplateWeekdays($daysOfWeek, $template->day_of_week);
            if ($daysOfWeek === []) {
                $errors[] = "Template #{$template->id} khong co thu hoc hop le.";

                continue;
            }

            $periods = $this->parsePeriodRange((string) $template->period_range);
            if ($periods === []) {
                $errors[] = "Template #{$template->id} co period_range khong hop le.";

                continue;
            }

            $templateSlotCount = 0;

            for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
                if (($templateStart && $date->lt($templateStart)) || ($templateEnd && $date->gt($templateEnd))) {
                    continue;
                }

                // Use module convention: 2..8 (Thu 2..Chu nhat)
                $dotw = $date->dayOfWeekIso + 1;
                if (! in_array($dotw, $daysOfWeek, true)) {
                    continue;
                }

                foreach ($periods as $period) {
                    $slotKey = implode('|', [$classId, $date->toDateString(), $period]);

                    if (isset($slotsByKey[$slotKey])) {
                        $errors[] = "Template #{$template->id} bi trung lop, ngay va tiet voi template khac.";

                        continue;
                    }

                    $slotsByKey[$slotKey] = [
                        'class_id' => $classId,
                        'subject_id' => $template->subject_id,
                        'date' => $date->toDateString(),
                        'day_of_week' => $dotw,
                        'period_number' => $period,
                        'period' => $template->session ?? ($period <= 5 ? 'Sang' : 'Chieu'),
                        'subject' => $template->subjects?->name ?? 'Chua xep mon',
                        'content' => '',
                        'slot_status' => 'planned',
                    ];
                    $templateSlotCount++;
                }
            }

            if ($templateSlotCount === 0) {
                $errors[] = "Template #{$template->id} khong sinh ra ngay hoc nao trong thang.";
            }
        }

        return [
            'applicable_templates' => $applicableTemplates,
            'slots' => $slotsByKey,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    private function upsertScheduleSlots(MonthlySchedule $monthlySchedule, array $slotsByKey): int
    {
        $timestamp = now();

        foreach ($slotsByKey as &$slot) {
            $slot['monthly_schedule_id'] = $monthlySchedule->id;
            $slot['created_at'] = $timestamp;
            $slot['updated_at'] = $timestamp;
        }
        unset($slot);

        $existingSlotKeys = ScheduleSlot::query()
            ->where('monthly_schedule_id', $monthlySchedule->id)
            ->get(['class_id', 'date', 'period_number'])
            ->mapWithKeys(function (ScheduleSlot $slot): array {
                $key = implode('|', [
                    $slot->class_id,
                    $slot->date->toDateString(),
                    $slot->period_number,
                ]);

                return [$key => true];
            })
            ->all();

        $created = count(array_diff_key($slotsByKey, $existingSlotKeys));

        foreach (array_chunk(array_values($slotsByKey), 500) as $slotChunk) {
            ScheduleSlot::query()->upsert(
                $slotChunk,
                ['monthly_schedule_id', 'date', 'period_number', 'class_id'],
                ['subject_id', 'day_of_week', 'period', 'subject', 'updated_at']
            );
        }

        return $created;
    }

    private function parsePeriodRange(string $periodRange): array
    {
        if (! preg_match('/^\s*(\d+)\s*(?:-\s*(\d+)\s*)?$/', $periodRange, $matches)) {
            return [];
        }

        $start = (int) $matches[1];
        $end = isset($matches[2]) ? (int) $matches[2] : $start;

        if ($start < 1 || $end < $start) {
            return [];
        }

        return range($start, $end);
    }

    private function normalizeTemplateWeekdays(array $rawDays, mixed $fallbackDay): array
    {
        $values = collect($rawDays)
            ->filter(fn ($item) => is_numeric($item))
            ->map(fn ($item) => (int) $item)
            ->values();

        if ($values->isEmpty() && is_numeric($fallbackDay)) {
            $values = collect([(int) $fallbackDay]);
        }

        // Preferred/current convention in semester flows.
        $currentConvention = $values
            ->filter(fn (int $dow) => $dow >= 2 && $dow <= 8)
            ->unique()
            ->sort()
            ->values();

        if ($currentConvention->isNotEmpty()) {
            return $currentConvention->all();
        }

        // Legacy fallback: 1..7 (ISO) -> 2..8.
        $isoConvention = $values
            ->filter(fn (int $dow) => $dow >= 1 && $dow <= 7)
            ->map(fn (int $dow) => $dow + 1)
            ->unique()
            ->sort()
            ->values();

        if ($isoConvention->isNotEmpty()) {
            return $isoConvention->all();
        }

        // Legacy fallback: 0..6 (Sun..Sat) -> 8,2..7.
        return $values
            ->filter(fn (int $dow) => $dow >= 0 && $dow <= 6)
            ->map(fn (int $dow) => $dow === 0 ? 8 : $dow + 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
