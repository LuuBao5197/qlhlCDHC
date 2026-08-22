<?php

namespace Modules\Training\Application\Management\SubjectLessons;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\SubjectLesson;

class ManageSubjectLessonsHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return SubjectLesson::class;
    }

    protected function relationships(): array
    {
        return ['subject', 'trainingProgram'];
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function filterableColumns(): array
    {
        return ['subject_id', 'training_program_id', 'is_regular_test'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        $subjectId = $request->input('subject_id');

        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            // The training program must already be linked to the chosen subject
            // (via subject_training_program) so a subject's lessons stay scoped per program.
            'training_program_id' => [
                'required',
                'integer',
                Rule::exists('subject_training_program', 'training_program_id')
                    ->where(fn ($query) => $query->where('subject_id', $subjectId)),
            ],
            'lesson_no' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('subject_lessons', 'lesson_no')
                    ->where(fn ($query) => $query
                        ->where('subject_id', $subjectId)
                        ->where('training_program_id', $request->input('training_program_id')))
                    ->ignore($id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'is_regular_test' => ['nullable', 'boolean'],
            'expected_periods' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ];
    }
}
