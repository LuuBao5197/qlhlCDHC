<?php

namespace Modules\Schedule\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Carbon\Carbon;

class ScheduleDatabaseSeeder extends Seeder
{
    public function run()
    {
        // Clean up old seeded data
        ScheduleSlot::query()->delete();
        MonthlySchedule::query()->delete();
        Plans::query()->delete();

        // Reset auto-increment if needed
        \DB::statement('ALTER TABLE schedule_slots AUTO_INCREMENT = 1');
        \DB::statement('ALTER TABLE monthly_schedules AUTO_INCREMENT = 1');
        \DB::statement('ALTER TABLE plans AUTO_INCREMENT = 1');

        // Create plan (semester)
        $plan = Plans::create([
            'name' => 'Kế hoạch huấn luyện HK1',
            'semester' => 1,
            'year' => 2026,
            'file_path' => 'plans/hk1_2026.pdf'
        ]);

        $classes = ['CD01', 'CD02'];
        $months = [2, 3, 4];
        $fullDay = true; // true: tạo đủ 1-9 mỗi ngày (morning+afternoon)

        foreach ($classes as $class) {
            foreach ($months as $month) {
                $monthly = MonthlySchedule::create([
                    'plan_id' => $plan->id,
                    'class_name' => $class,
                    'month' => $month,
                    'year' => 2026
                ]);

                $startDate = Carbon::create(2026, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();

                $subjectList = ['Chiến thuật', 'Thể lực', 'Bắn súng', 'Điều lệnh', 'Hậu cần'];
                $subjectDays = 3; // mỗi môn học 3 ngày liên tiếp
                $dayCounter = 0;

                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    // Skip Sunday
                    if ($date->dayOfWeek === Carbon::SUNDAY) {
                        continue;
                    }

                    $blockIndex = intdiv($dayCounter, $subjectDays);
                    $morningSubject = $subjectList[$blockIndex % count($subjectList)];
                    $afternoonSubject = $subjectList[($blockIndex + 1) % count($subjectList)];

                    // Morning 1-5 (same mon theo block)
                    for ($period = 1; $period <= 5; $period++) {
                        ScheduleSlot::create([
                            'monthly_schedule_id' => $monthly->id,
                            'date' => $date->toDateString(),
                            'day_of_week' => $date->dayOfWeek,
                            'period' => 'Sáng',
                            'period_number' => $period,
                            'subject' => $morningSubject,
                            'content' => 'Buổi sáng ' . $morningSubject
                        ]);
                    }

                    // Afternoon 6-9 (same mon theo block)
                    for ($period = 6; $period <= 9; $period++) {
                        ScheduleSlot::create([
                            'monthly_schedule_id' => $monthly->id,
                            'date' => $date->toDateString(),
                            'day_of_week' => $date->dayOfWeek,
                            'period' => 'Chiều',
                            'period_number' => $period,
                            'subject' => $afternoonSubject,
                            'content' => 'Buổi chiều ' . $afternoonSubject
                        ]);
                    }

                    $dayCounter++;
                }
            }
        }
    }

    private function randomSubject()
    {
        $subjects = [
            'Chiến thuật',
            'Thể lực',
            'Bắn súng',
            'Điều lệnh',
            'Hậu cần'
        ];

        return $subjects[array_rand($subjects)];
    }

    private function randomTopic()
    {
        $topics = [
            'Ôn tập',
            'Thực hành',
            'Kiểm tra',
            'Giải bài',
            'Đánh giá'
        ];

        return $topics[array_rand($topics)];
    }

    private function generatePeriodBlocks()
    {
        $subjects = [
            'Chiến thuật',
            'Thể lực',
            'Bắn súng',
            'Điều lệnh',
            'Hậu cần'
        ];

        $blocks = [];
        $current = 1;

        while ($current <= 9) {
            $nextMax = min(9, $current + rand(1, 3) - 1);
            $blocks[] = [
                'period_number' => $current,
                'period_end' => $nextMax,
                'subject' => $subjects[array_rand($subjects)],
                'content' => 'Học liên tục từ tiết ' . $current . ' đến ' . $nextMax
            ];
            $current = $nextMax + 1;
        }

        return $blocks;
    }
}
