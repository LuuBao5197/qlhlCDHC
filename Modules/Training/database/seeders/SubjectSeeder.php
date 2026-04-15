<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;
use Illuminate\Database\Seeder;
use Modules\Schedule\Models\ScheduleSlot;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $defaultDepartment = Department::where('code', 'KCNTT')->first() ?? Department::query()->first();

        // Get subjects from existing ScheduleSlots, or use default subjects if none exist
        $subjectNames = ScheduleSlot::query()
            ->whereNotNull('subject')
            ->pluck('subject')
            ->unique()
            ->filter()
            ->values()
            ->all();

        // If no subjects found in ScheduleSlots, use default subjects
        if (empty($subjectNames)) {
            $subjectNames = [
                'Toán học',
                'Vật lý',
                'Hóa học',
                'Tin học',
                'Lập trình Python'
            ];
        }

        foreach ($subjectNames as $index => $subjectName) {
            Subject::updateOrCreate(
                ['name' => $subjectName],
                [
                    'department_id' => $defaultDepartment?->id,
                    'code' => 'SUB-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'total_periods' => 30,
                    'status' => 'active',
                ]
            );
        }

        ScheduleSlot::query()->whereNotNull('subject')->get()->each(function (ScheduleSlot $slot) {
            $subject = Subject::where('name', $slot->subject)->first();
            if ($subject) {
                $slot->update(['subject_id' => $subject->id]);
            }
        });
    }
}
