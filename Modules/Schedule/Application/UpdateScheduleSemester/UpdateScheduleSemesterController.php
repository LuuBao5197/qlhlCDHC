<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;
use Modules\Training\Models\TrainingProgram;

class UpdateScheduleSemesterController extends Controller
{
    public function __construct(
        private UpdateScheduleSemesterHandler $handler,
        private PlanRuleFormConverter $converter,
        private PlanClassExportBuilder $exporter
    ) {}

    public function showForm($id)
    {
        $plan = Plans::with('trainingBatch.trainingProgram', 'planTemplates.trainingClass', 'planTemplates.subjects')
            ->with(['semesterEvents.trainingClass'])
            ->findOrFail($id);

        if (!PlanEditGuard::canEdit($plan, request()->user())) {
            return redirect()->route('schedule.index')->with('error', PlanEditGuard::DENIED_MESSAGE);
        }

        $classesQuery = TrainingClass::query()
            ->orderBy('code');

        if ($plan->training_batch_id) {
            $classesQuery->where('training_batch_id', $plan->training_batch_id);
        }

        $classes = $classesQuery->get(['id', 'training_batch_id', 'code', 'name']);

        $trainingProgram = $plan->trainingBatch?->trainingProgram;
        $programSubjects = $trainingProgram ? $trainingProgram->subjects()->orderBy('code')->get(['subjects.id', 'subjects.code', 'subjects.name']) : collect();

        $subjects = $programSubjects->isNotEmpty()
            ? $programSubjects
            : Subject::query()->orderBy('code')->get(['id', 'code', 'name']);

        $subjectSuggestions = $subjects
            ->map(fn(Subject $subject) => [
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

        $oldRules = $this->converter->templatesToRules($plan->planTemplates);

        $selectedClassIds = $plan->planTemplates
            ->pluck('class_id')
            ->merge($plan->semesterEvents->pluck('class_id'))
            ->unique()
            ->values()
            ->toArray();

        $oldGlobalEvents = $this->converter->globalEventsToForm($plan->semesterEvents->whereNull('class_id'));
        $oldClassEvents = $this->converter->classEventsToForm($plan->semesterEvents->whereNotNull('class_id'));

        $existingPlanKeys = Plans::query()
            ->whereNotNull('training_batch_id')
            ->where('id', '!=', $plan->id)
            ->get(['training_batch_id', 'semester', 'year'])
            ->map(fn (Plans $item) => implode('|', [
                $item->training_batch_id,
                $item->semester,
                $item->year,
            ]))
            ->values();

        $globalEventTypes = [
            ['value' => 'holiday', 'label' => 'Nghi le', 'color' => '#ffedd5'],
        ];

        $classEventTypes = [
            ['value' => 'review', 'label' => 'On thi', 'color' => '#dbeafe'],
            ['value' => 'exam', 'label' => 'Thi', 'color' => '#fee2e2'],
            ['value' => 'other', 'label' => 'Su kien khac', 'color' => '#ede9fe'],
        ];

        return view('schedule::ScheduleSemester.edit', [
            'plan' => $plan,
            'classes' => $classes,
            'subjects' => $subjects,
            'subjectSuggestions' => $subjectSuggestions,
            'weekdayOptions' => $weekdayOptions,
            'semesterCount' => max(
                (int) ($trainingProgram?->semester_count ?? TrainingProgram::DEFAULT_SEMESTER_COUNT),
                (int) $plan->semester
            ),
            'oldRules' => $oldRules,
            'oldGlobalEvents' => $oldGlobalEvents,
            'oldClassEvents' => $oldClassEvents,
            'selectedClassIds' => $selectedClassIds,
            'existingPlanKeys' => $existingPlanKeys,
            'globalEventTypes' => $globalEventTypes,
            'classEventTypes' => $classEventTypes,
        ]);
    }

    public function exportClass($id, $classId)
    {
        $plan = Plans::with('planTemplates.subjects', 'semesterEvents')->findOrFail($id);
        $this->authorizeExport($plan);
        $class = TrainingClass::query()->findOrFail($classId);

        $path = $this->exporter->writeXlsx($plan, $class);

        return response()->download($path, $this->exportFileName($plan, $class), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function exportAll($id)
    {
        $plan = Plans::with('planTemplates.subjects', 'semesterEvents')->findOrFail($id);
        $this->authorizeExport($plan);

        $classIds = $plan->planTemplates->pluck('class_id')
            ->merge($plan->semesterEvents->pluck('class_id'))
            ->filter()
            ->unique()
            ->values();
        $classes = TrainingClass::query()->whereIn('id', $classIds)->orderBy('code')->get();

        abort_if($classes->isEmpty(), 404, 'Kế hoạch chưa có dữ liệu lớp nào để xuất.');

        $zipPath = tempnam(sys_get_temp_dir(), 'plan_zip_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $tempFiles = [];
        foreach ($classes as $class) {
            $file = $this->exporter->writeXlsx($plan, $class);
            $tempFiles[] = $file;
            $zip->addFile($file, $this->exportFileName($plan, $class));
        }
        $zip->close();
        foreach ($tempFiles as $file) {
            @unlink($file);
        }

        return response()->download($zipPath, sprintf('ke-hoach-hk%d-%d-tat-ca-lop.zip', $plan->semester, $plan->year))
            ->deleteFileAfterSend(true);
    }

    private function authorizeExport(Plans $plan): void
    {
        $user = request()->user();
        abort_unless($user && ($user->isTrainingOffice() || $user->isAdmin()), 403);
    }

    private function exportFileName(Plans $plan, TrainingClass $class): string
    {
        return sprintf('%s-hk%d-%d.xlsx', preg_replace('/[^A-Za-z0-9_-]/', '_', $class->code), $plan->semester, $plan->year);
    }

    public function __invoke(UpdateScheduleSemesterRequest $request, $id)
    {
        return $this->handler->handle($request, $id);
    }
}
