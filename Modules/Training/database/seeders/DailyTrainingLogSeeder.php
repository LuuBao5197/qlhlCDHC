<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\DailyTrainingLog;
use Modules\Training\Models\SubjectLesson;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Schedule\Models\ScheduleSlot;

class DailyTrainingLogSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@example.com')->first() ?? User::query()->first();
        $checker = User::where('email', 'training@example.com')->first() ?? User::query()->first();

        ScheduleSlot::query()->with('subjectModel')->orderBy('id')->take(20)->get()->each(function (ScheduleSlot $slot) use ($teacher, $checker) {
            $lesson = $slot->subject_id
                ? SubjectLesson::where('subject_id', $slot->subject_id)->orderBy('lesson_no')->first()
                : null;

            $slot->update([
                'teacher_id' => $slot->teacher_id ?? $teacher?->id,
                'subject_lesson_id' => $slot->subject_lesson_id ?? $lesson?->id,
            ]);

            DailyTrainingLog::updateOrCreate(
                ['schedule_slot_id' => $slot->id],
                [
                    'teacher_id' => $slot->teacher_id ?? $teacher?->id,
                    'actual_date' => $slot->date,
                    'actual_period_number' => $slot->period_number,
                    'result_status' => 'completed',
                    'actual_content' => $slot->content ?? $slot->subject ?? 'Da thuc hien theo ke hoach',
                    'issue_note' => null,
                    'checked_by' => $checker?->id,
                    'checked_at' => now(),
                ]
            );
        });
    }
}
