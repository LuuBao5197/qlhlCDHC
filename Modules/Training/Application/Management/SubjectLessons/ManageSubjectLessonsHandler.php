<?php

namespace Modules\Training\Application\Management\SubjectLessons;

use Illuminate\Http\Request;
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
        return ['subject'];
    }

    protected function searchColumns(): array
    {
        return ['title'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'lesson_no' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'is_regular_test' => ['nullable', 'boolean'],
            'expected_periods' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string'],
        ];
    }
}