<?php

namespace Modules\Schedule\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;

class PlanTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plans::query()->latest('id')->first();
        if (! $plan) {
            return;
        }

        $classes = TrainingClass::query()->limit(3)->get();
        $subjects = Subject::query()->limit(5)->get();
        if ($classes->isEmpty() || $subjects->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $templateRows = [];

        $schedulePatterns = [
            [
                'name' => 'Pattern 1',
                'schedules' => [
                    ['subjects' => [0], 'days' => [2, 4, 6], 'session' => 'Sang', 'period' => '1-5', 'date_offset' => 0],
                    ['subjects' => [1], 'days' => [3, 5], 'session' => 'Sang', 'period' => '1-5', 'date_offset' => 0],
                    ['subjects' => [2], 'days' => [2], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 0],
                    ['subjects' => [3], 'days' => [3], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 0],
                    ['subjects' => [4], 'days' => [4], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 0],
                ],
            ],
            [
                'name' => 'Pattern 2',
                'schedules' => [
                    ['subjects' => [0], 'days' => [2, 3], 'session' => 'Sang', 'period' => '1-5', 'date_offset' => 14],
                    ['subjects' => [1], 'days' => [4, 5], 'session' => 'Sang', 'period' => '1-5', 'date_offset' => 14],
                    ['subjects' => [2], 'days' => [6], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 14],
                    ['subjects' => [3], 'days' => [7], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 14],
                    ['subjects' => [4], 'days' => [2, 4], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 14],
                ],
            ],
            [
                'name' => 'Pattern 3',
                'schedules' => [
                    ['subjects' => [0], 'days' => [2, 5], 'session' => 'Sang', 'period' => '1-5', 'date_offset' => 28],
                    ['subjects' => [1], 'days' => [3, 4], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 28],
                    ['subjects' => [2], 'days' => [6], 'session' => 'Sang', 'period' => '1-5', 'date_offset' => 28],
                    ['subjects' => [3], 'days' => [7], 'session' => 'Sang', 'period' => '1-5', 'date_offset' => 28],
                    ['subjects' => [4], 'days' => [2, 7], 'session' => 'Chieu', 'period' => '6-9', 'date_offset' => 28],
                ],
            ],
        ];

        foreach ($classes as $classIndex => $class) {
            $pattern = $schedulePatterns[$classIndex] ?? $schedulePatterns[0];

            foreach ($pattern['schedules'] as $schedule) {
                $startDate = Carbon::parse($plan->effective_from ?? '2026-01-01')
                    ->addDays($schedule['date_offset']);
                $endDate = $startDate->copy()->addDays(180);

                foreach ($schedule['subjects'] as $subjectIndex) {
                    $subject = $subjects[$subjectIndex] ?? null;
                    if (! $subject) {
                        continue;
                    }

                    $templateRows[] = [
                        'plan_id' => $plan->id,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'day_of_week' => $schedule['days'][0] ?? 2,
                        'days_of_week' => json_encode($schedule['days']),
                        'session' => $schedule['session'],
                        'period_range' => $schedule['period'],
                        'description' => "Lịch {$pattern['name']} - Lớp {$class->code} - Môn {$subject->name}",
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::table('plan_templates')->insert($templateRows);
    }
}
