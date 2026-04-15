<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;
use Illuminate\Database\Seeder;

class SubjectLessonSeeder extends Seeder
{
    public function run(): void
    {
        Subject::query()->each(function (Subject $subject) {
            for ($lessonNo = 1; $lessonNo <= 3; $lessonNo++) {
                SubjectLesson::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'lesson_no' => $lessonNo,
                    ],
                    [
                        'title' => $subject->name . ' - Bai ' . $lessonNo,
                        'expected_periods' => 2,
                        'note' => 'Noi dung scaffold cho bai hoc',
                    ]
                );
            }
        });
    }
}
