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

        // Định nghĩa các lịch học khác nhau cho mỗi lớp
        // Mỗi mục (subjects, days, session, period) chỉ có 1 môn để tránh trùng lặp
        $schedulePatterns = [
            [
                'name' => 'Pattern 1',
                'schedules' => [
                    ['subjects' => [0], 'days' => [2, 4, 6], 'session' => 'Sáng', 'period' => '1-5', 'date_offset' => 0],       // Môn 1: Thứ 2,4,6 Sáng tiết 1-5
                    ['subjects' => [1], 'days' => [3, 5], 'session' => 'Sáng', 'period' => '1-5', 'date_offset' => 0],         // Môn 2: Thứ 3,5 Sáng tiết 1-5
                    ['subjects' => [2], 'days' => [2], 'session' => 'Chiều', 'period' => '6-9', 'date_offset' => 0],          // Môn 3: Thứ 2 Chiều tiết 6-9
                    ['subjects' => [3], 'days' => [3], 'session' => 'Chiều', 'period' => '6-9', 'date_offset' => 0],          // Môn 4: Thứ 3 Chiều tiết 6-9
                    ['subjects' => [4], 'days' => [4], 'session' => 'Chiều', 'period' => '6-9', 'date_offset' => 0],          // Môn 5: Thứ 4 Chiều tiết 6-9
                ]
            ],
            [
                'name' => 'Pattern 2',
                'schedules' => [
                    ['subjects' => [0], 'days' => [2, 3], 'session' => 'Sáng', 'period' => '1-5', 'date_offset' => 14],        // Môn 1: Thứ 2,3 Sáng tiết 1-5
                    ['subjects' => [1], 'days' => [4, 5], 'session' => 'Sáng', 'period' => '1-5', 'date_offset' => 14],        // Môn 2: Thứ 4,5 Sáng tiết 1-5
                    ['subjects' => [2], 'days' => [6], 'session' => 'Chiều', 'period' => '6-9', 'date_offset' => 14],          // Môn 3: Thứ 6 Chiều tiết 6-9
                    ['subjects' => [3], 'days' => [7], 'session' => 'Chiều', 'period' => '6-9', 'date_offset' => 14],          // Môn 4: Thứ 7 Chiều tiết 6-9
                    ['subjects' => [4], 'days' => [2, 4], 'session' => 'Chiều', 'period' => '1-5', 'date_offset' => 14],       // Môn 5: Thứ 2,4 Chiều tiết 1-5
                ]
            ],
            [
                'name' => 'Pattern 3',
                'schedules' => [
                    ['subjects' => [0], 'days' => [2, 5], 'session' => 'Sáng', 'period' => '1-5', 'date_offset' => 28],        // Môn 1: Thứ 2,5 Sáng tiết 1-5
                    ['subjects' => [1], 'days' => [3, 4], 'session' => 'Chiều', 'period' => '6-9', 'date_offset' => 28],       // Môn 2: Thứ 3,4 Chiều tiết 6-9
                    ['subjects' => [2], 'days' => [6], 'session' => 'Sáng', 'period' => '1-5', 'date_offset' => 28],           // Môn 3: Thứ 6 Sáng tiết 1-5
                    ['subjects' => [3], 'days' => [7], 'session' => 'Sáng', 'period' => '1-5', 'date_offset' => 28],           // Môn 4: Thứ 7 Sáng tiết 1-5
                    ['subjects' => [4], 'days' => [3, 5], 'session' => 'Sáng', 'period' => '6-9', 'date_offset' => 28],        // Môn 5: Thứ 3,5 Sáng tiết 6-9
                ]
            ]
        ];

        foreach ($classes as $classIndex => $class) {
            $pattern = $schedulePatterns[$classIndex] ?? $schedulePatterns[0];

            foreach ($pattern['schedules'] as $schedule) {
                // Tính toán start_date và end_date với offset
                $startDate = Carbon::parse($plan->effective_from ?? '2026-01-01')
                    ->addDays($schedule['date_offset']);
                $endDate = $startDate->copy()->addDays(180); // Khoảng 6 tháng cho mỗi lịch

                foreach ($schedule['subjects'] as $subjectIndex) {
                    $subject = $subjects[$subjectIndex] ?? null;
                    if (! $subject) {
                        continue;
                    }

                    $templateRows[] = [
                        'plan_id' => $plan->id,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'day_of_week' => $schedule['days'][0] ?? 2,  // Giữ lại cho backward compatibility
                        'days_of_week' => json_encode($schedule['days']),  // Danh sách ngày thực tế
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
