<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
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

        $batchProgramMap = $trainingBatches
            ->mapWithKeys(fn (TrainingBatch $batch) => [(string) $batch->id => $batch->training_program_id])
            ->all();

        $programSubjectCodes = DB::table('subject_training_program')
            ->join('subjects', 'subjects.id', '=', 'subject_training_program.subject_id')
            ->select('subject_training_program.training_program_id', 'subjects.code')
            ->get()
            ->groupBy('training_program_id')
            ->map(fn ($rows) => $rows->pluck('code')->values())
            ->all();

        $weekdayOptions = [
            ['value' => 2, 'label' => 'Thu 2'],
            ['value' => 3, 'label' => 'Thu 3'],
            ['value' => 4, 'label' => 'Thu 4'],
            ['value' => 5, 'label' => 'Thu 5'],
            ['value' => 6, 'label' => 'Thu 6'],
            ['value' => 7, 'label' => 'Thu 7'],
            ['value' => 8, 'label' => 'Chu nhat'],
        ];

        $globalEventTypes = [
            ['value' => 'holiday', 'label' => 'Nghi le', 'color' => '#ffedd5'],
        ];

        $classEventTypes = [
            ['value' => 'review', 'label' => 'On thi', 'color' => '#dbeafe'],
            ['value' => 'exam', 'label' => 'Thi', 'color' => '#fee2e2'],
            ['value' => 'other', 'label' => 'Su kien khac', 'color' => '#ede9fe'],
        ];

        return view('schedule::ScheduleSemester.create', [
            'trainingBatches' => $trainingBatches,
            'classes' => $classes,
            'existingPlanKeys' => $existingPlanKeys,
            'subjects' => $subjects,
            'subjectSuggestions' => $subjectSuggestions,
            'batchProgramMap' => $batchProgramMap,
            'programSubjectCodes' => $programSubjectCodes,
            'weekdayOptions' => $weekdayOptions,
            'globalEventTypes' => $globalEventTypes,
            'classEventTypes' => $classEventTypes,
        ]);
    }

    public function downloadImportTemplate()
    {
        return response()->download(
            module_path('Schedule', 'resources/templates/plan-template-import.csv'),
            'plan-template-import.csv',
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="plan-template-import.csv"',
            ]
        );
    }

    public function __invoke(CreateScheduleSemesterRequest $request)
    {
        return $this->handler->handle($request);
    }
}
