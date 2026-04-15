<?php

namespace Modules\Training\Database\Factories\Concerns;

use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\ScheduleSlot;

trait ResolvesScheduleReferences
{
    protected function firstOrCreatePlan(): Plans
    {
        return Plans::query()->first() ?? Plans::create([
            'name' => 'Scaffold Plan',
            'semester' => 1,
            'year' => (int) date('Y'),
            'status' => 'draft',
            'current_step' => 'draft',
            'approved_version' => 1,
        ]);
    }

    protected function firstOrCreateMonthlySchedule(): MonthlySchedule
    {
        return MonthlySchedule::query()->first() ?? MonthlySchedule::create([
            'plan_id' => $this->firstOrCreatePlan()->id,
            'class_name' => 'SCF01',
            'month' => 1,
            'year' => (int) date('Y'),
            'status' => 'draft',
        ]);
    }

    protected function firstOrCreateScheduleSlot(): ScheduleSlot
    {
        return ScheduleSlot::query()->first() ?? ScheduleSlot::create([
            'monthly_schedule_id' => $this->firstOrCreateMonthlySchedule()->id,
            'date' => now()->startOfDay(),
            'day_of_week' => (int) now()->dayOfWeek,
            'period' => 'Period 1',
            'period_number' => 1,
            'subject' => 'Sample Subject',
            'content' => 'Sample content',
            'slot_status' => 'planned',
        ]);
    }
}
