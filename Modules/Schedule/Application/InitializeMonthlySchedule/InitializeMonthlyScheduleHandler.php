<?php
namespace Modules\Schedule\Application\InitializeMonthlySchedule;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\PlanTemplates;
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

        return DB::transaction(function () use ($targetMonth, $targetYear, $targetDate, $targetMonthStart, $targetMonthEnd, $request) {
            $plans = Plans::query()
                ->whereIn('status', ['draft', 'submitted', 'approved'])
                ->whereNotNull('effective_from')
                ->whereNotNull('effective_to')
                ->get();

            $createdCount = 0;
            $slotCount = 0;
            $monthlyScheduleIds = [];
            $eligiblePlans = 0;
            $plansWithTemplates = 0;

            foreach ($plans as $plan) {
                $planStart = Carbon::parse($plan->effective_from)->startOfMonth();
                $planEnd = Carbon::parse($plan->effective_to)->endOfMonth();

                if ($targetDate->lt($planStart) || $targetDate->gt($planEnd)) {
                    continue;
                }

                $eligiblePlans++;

                $templates = PlanTemplates::query()
                    ->where('plan_id', $plan->id)
                    ->where(function ($query) use ($targetMonthStart, $targetMonthEnd) {
                        $query
                            // If template has date bounds, only apply when it overlaps target month.
                            ->where(function ($q) use ($targetMonthStart, $targetMonthEnd) {
                                $q->whereNotNull('start_date')
                                    ->whereNotNull('end_date')
                                    ->whereDate('start_date', '<=', $targetMonthEnd->toDateString())
                                    ->whereDate('end_date', '>=', $targetMonthStart->toDateString());
                            })
                            // Backward compatibility for old templates without explicit date range.
                            ->orWhere(function ($q) {
                                $q->whereNull('start_date')
                                    ->whereNull('end_date');
                            });
                    })
                    ->with(['subjects'])
                    ->get();

                if ($templates->isEmpty()) {
                    continue;
                }

                $plansWithTemplates++;

                // One MonthlySchedule per plan+month+year (class distinction is at ScheduleSlot level)
                $monthlySchedule = MonthlySchedule::query()
                    ->where('plan_id', $plan->id)
                    ->where('month', $targetMonth)
                    ->where('year', $targetYear)
                    ->first();

                if (!$monthlySchedule) {
                    $monthlySchedule = MonthlySchedule::create([
                        'plan_id' => $plan->id,
                        'month' => $targetMonth,
                        'year' => $targetYear,
                        'created_by' => $request->user()?->id,
                        'status' => 'draft',
                    ]);
                    $createdCount++;
                }

                $monthlyScheduleIds[] = $monthlySchedule->id;

                $slotCount += $this->createScheduleSlots($monthlySchedule, $templates, $targetMonth, $targetYear);
            }

            if ($eligiblePlans === 0) {
                throw ValidationException::withMessages([
                    'month' => "Khong co ke hoach hoc ky nao hieu luc trong thang {$targetMonth}/{$targetYear}.",
                ]);
            }

            if ($plansWithTemplates === 0) {
                throw ValidationException::withMessages([
                    'month' => "Khong co mau lich hoc (plan_templates) hieu luc cho thang {$targetMonth}/{$targetYear}. Vui long tao mau truoc khi khoi tao lich thang.",
                ]);
            }

            return [
                'success' => true,
                'created_schedules' => $createdCount,
                'created_slots' => $slotCount,
                'monthly_schedule_ids' => $monthlyScheduleIds,
            ];
        });
    }

    private function createScheduleSlots(
        MonthlySchedule $monthlySchedule,
        iterable $templates,
        int $targetMonth,
        int $targetYear
    ): int {
        $slotKeys = [];
        $created = 0;

        $monthStart = Carbon::create($targetYear, $targetMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();

        foreach ($templates as $template) {
            $classId = $template->class_id;
            $templateStart = $template->start_date ? Carbon::parse($template->start_date)->startOfDay() : null;
            $templateEnd = $template->end_date ? Carbon::parse($template->end_date)->endOfDay() : null;

            $daysOfWeek = is_array($template->days_of_week)
                ? $template->days_of_week
                : (json_decode($template->days_of_week, true) ?: [$template->day_of_week]);

            // Ensure integer comparison
            $daysOfWeek = array_map('intval', $daysOfWeek);

            $periodParts = explode('-', $template->period_range);
            $periodStart = (int) $periodParts[0];
            $periodEnd = (int) ($periodParts[1] ?? $periodStart);
            $periods = range($periodStart, $periodEnd);

            for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
                if (($templateStart && $date->lt($templateStart)) || ($templateEnd && $date->gt($templateEnd))) {
                    continue;
                }

                $dotw = $date->dayOfWeekIso;
                if (!in_array($dotw, $daysOfWeek, true)) {
                    continue;
                }

                foreach ($periods as $period) {
                    $slotKey = implode('|', [$classId, $date->toDateString(), $period]);

                    if (isset($slotKeys[$slotKey])) {
                        continue;
                    }

                    // Check DB to avoid duplicates on re-run
                    $exists = ScheduleSlot::query()
                        ->where('monthly_schedule_id', $monthlySchedule->id)
                        ->where('class_id', $classId)
                        ->whereDate('date', $date->toDateString())
                        ->where('period_number', $period)
                        ->exists();

                    if ($exists) {
                        $slotKeys[$slotKey] = true;
                        continue;
                    }

                    $slotKeys[$slotKey] = true;

                    ScheduleSlot::create([
                        'monthly_schedule_id' => $monthlySchedule->id,
                        'class_id' => $classId,
                        'subject_id' => $template->subject_id,
                        'date' => $date->toDateString(),
                        'day_of_week' => $dotw,
                        'period_number' => $period,
                        'period' => $template->session ?? ($period <= 5 ? 'Sang' : 'Chieu'),
                        'subject' => $template->subjects?->name ?? 'Chua xep mon',
                        'content' => '',
                        'slot_status' => 'planned',
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }
}
