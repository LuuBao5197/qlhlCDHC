<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingBatch;
use Modules\Training\Models\TrainingClass;

class CreateScheduleSemesterController extends Controller
{
    public function __construct(
        private CreateScheduleSemesterHandler $handler
    ) {}


    public function showForm()
    {
        $trainingBatches = TrainingBatch::query()
            ->with('trainingProgram:id,code,name')
            ->withCount('classes')
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'training_program_id', 'code', 'name', 'status']);

        $classes = TrainingClass::query()
            ->orderBy('code')
            ->get(['id', 'training_batch_id', 'code', 'name']);

        $existingPlanKeys = Plans::query()
            ->whereNotNull('training_batch_id')
            ->get(['training_batch_id', 'semester', 'year'])
            ->map(fn (Plans $plan) => implode('|', [
                $plan->training_batch_id,
                $plan->semester,
                $plan->year,
            ]))
            ->values();

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
            'trainingBatches' => $trainingBatches,
            'classes' => $classes,
            'existingPlanKeys' => $existingPlanKeys,
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
