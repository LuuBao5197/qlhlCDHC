<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use App\Http\Controllers\Controller;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;

class CreateScheduleSemesterController extends Controller
{
    public function __construct(
        private CreateScheduleSemesterHandler $handler
    ) {}

    public function showForm()
    {
        $classes = TrainingClass::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $subjects = Subject::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $subjectSuggestions = $subjects
            ->map(fn (Subject $subject) => [
                'code' => $subject->code,
                'name' => $subject->name,
            ])
            ->values();

        $weekdayOptions = [
            ['value' => 2, 'label' => 'Thu 2'],
            ['value' => 3, 'label' => 'Thu 3'],
            ['value' => 4, 'label' => 'Thu 4'],
            ['value' => 5, 'label' => 'Thu 5'],
            ['value' => 6, 'label' => 'Thu 6'],
            ['value' => 7, 'label' => 'Thu 7'],
            ['value' => 8, 'label' => 'Chu nhat'],
        ];

        return view('schedule::ScheduleSemester.create', [
            'classes' => $classes,
            'subjects' => $subjects,
            'subjectSuggestions' => $subjectSuggestions,
            'weekdayOptions' => $weekdayOptions,
        ]);
    }

    public function downloadImportTemplate()
    {
        return response()->download(
            module_path('Schedule', 'resources/templates/plan-template-import.csv'),
            'plan-template-import.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    public function __invoke(CreateScheduleSemesterRequest $request)
    {
        return $this->handler->handle($request);
    }
}
