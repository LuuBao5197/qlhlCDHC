<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use App\Support\AdminBackfillContext;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\Shared\ScheduleSemesterAnalyzer;
use Modules\Schedule\Application\Shared\ScheduleValidationReport;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\SemesterEvent;
use Modules\Training\Models\TrainingBatch;
use Modules\Training\Models\TrainingClass;

class CreateScheduleSemesterHandler
{
    public function __construct(
        private ScheduleSemesterAnalyzer $analyzer
    ) {}

    /**
     * Runs every validation phase against the submitted input and records all
     * problems in $report, without touching the database. Used both by the form
     * request (to show every error at once) and by handle() before saving.
     *
     * @return array{templates: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>, classMap: array<string, TrainingClass>, planStart: ?Carbon, planEnd: ?Carbon}
     */
    public function analyze(CreateScheduleSemesterRequest $request, ScheduleValidationReport $report): array
    {
        $empty = ['templates' => [], 'events' => [], 'classMap' => [], 'planStart' => null, 'planEnd' => null];

        $trainingBatchId = $request->input('training_batch_id');
        if (!is_numeric($trainingBatchId)) {
            return $empty;
        }

        try {
            $planStart = Carbon::parse((string) $request->input('start_date'))->startOfDay();
            $planEnd = Carbon::parse((string) $request->input('end_date'))->endOfDay();
        } catch (\Throwable) {
            return $empty;
        }

        // Missing/invalid plan dates and empty class lists are reported by the
        // form request itself.
        $classMap = $this->resolveClasses((int) $trainingBatchId);
        if ($planEnd->lt($planStart) || $classMap === []) {
            return $empty;
        }

        $classIds = collect($classMap)->map(fn (TrainingClass $class): int => (int) $class->id)->unique()->values()->all();

        $result = $this->analyzer->analyze(
            $report,
            $request->input('class_tab_rules', []),
            $request->input('global_semester_events', []),
            $request->input('class_semester_events', []),
            $request->file('import_file', []),
            $classMap,
            $this->resolveAllowedSubjectIds((int) $trainingBatchId),
            $planStart,
            $planEnd,
            $classIds
        );

        return $result + ['classMap' => $classMap, 'planStart' => $planStart, 'planEnd' => $planEnd];
    }

    public function handle(CreateScheduleSemesterRequest $request)
    {
        $validated = $request->validated();

        $semester = (int) $validated['semester'];
        $year = (int) $validated['year'];
        $trainingBatchId = (int) $validated['training_batch_id'];

        $report = new ScheduleValidationReport();
        $analysis = $this->analyze($request, $report);
        $report->throwIfFailed();

        $planStart = $analysis['planStart'];
        $planEnd = $analysis['planEnd'];
        $classMap = $analysis['classMap'];
        $templateEntries = $analysis['templates'];
        $semesterEvents = $analysis['events'];

        if ($planStart === null || $templateEntries === []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => 'Không có dữ liệu lịch tổng quát để tạo kế hoạch.',
            ]);
        }

        $duplicatePlan = Plans::query()
            ->with('trainingBatch')
            ->where('training_batch_id', $trainingBatchId)
            ->where('semester', $semester)
            ->where('year', $year)
            ->first();

        if ($duplicatePlan) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Khóa đào tạo ' . ($duplicatePlan->trainingBatch?->code ?? 'đã chọn') . ' đã có kế hoạch học kỳ ' . $semester . ' năm ' . $year . '.',
            ]);
        }

        $plan = DB::transaction(function () use (
            $request,
            $semester,
            $year,
            $validated,
            $templateEntries,
            $semesterEvents,
            $planStart,
            $planEnd,
            $trainingBatchId
        ) {
            $isAdminBackfill = AdminBackfillContext::isActive($request);

            $plan = Plans::query()->create([
                'training_batch_id' => $trainingBatchId,
                'name' => sprintf('Kế hoạch học kỳ %d - %d', $semester, $year),
                'semester' => $semester,
                'year' => $year,
                'description' => $validated['description'] ?? null,
                'effective_from' => $planStart->toDateString(),
                'effective_to' => $planEnd->toDateString(),
                'status' => $isAdminBackfill ? 'approved' : 'draft',
                'current_step' => $isAdminBackfill ? 'completed' : 'draft',
                'created_by' => $request->user()?->id,
                'submitted_by' => $isAdminBackfill ? $request->user()?->id : null,
                'submitted_at' => $isAdminBackfill ? now() : null,
            ]);

            foreach ($templateEntries as $entry) {
                PlanTemplates::query()->create(
                    Arr::only(
                        $entry + ['plan_id' => $plan->id],
                        [
                            'plan_id',
                            'class_id',
                            'subject_id',
                            'day_of_week',
                            'days_of_week',
                            'session',
                            'period_range',
                            'description',
                            'start_date',
                            'end_date',
                        ]
                    )
                );
            }

            foreach ($semesterEvents as $event) {
                SemesterEvent::query()->create(
                    Arr::only(
                        $event + ['plan_id' => $plan->id],
                        [
                            'plan_id',
                            'class_id',
                            'event_type',
                            'title',
                            'start_date',
                            'end_date',
                            'period_from',
                            'period_to',
                            'color',
                            'note',
                            'sort_order',
                        ]
                    )
                );
            }

            return $plan;
        });

        AdminBackfillContext::log($request, 'schedule_semester_create', $plan);

        $firstClass = reset($classMap);
        $className = $firstClass ? $firstClass->code : null;

        $redirectUrl = route('schedule.semester.public', [
            'semester' => $plan->semester,
            'year' => $plan->year,
            'className' => $className,
            'training_batch_id' => $plan->training_batch_id,
        ]);
        $successMessage = 'Đã tạo kế hoạch học kỳ và lịch tổng quát thành công.';

        if ($request->expectsJson()) {
            session()->flash('success', $successMessage);

            return response()->json(['redirect' => $redirectUrl]);
        }

        return redirect($redirectUrl)->with('success', $successMessage);
    }

    /**
     * @return array<int, int>|null Null means no restriction (program has not configured a subject list yet).
     */
    private function resolveAllowedSubjectIds(int $trainingBatchId): ?array
    {
        $trainingProgramId = TrainingBatch::query()->where('id', $trainingBatchId)->value('training_program_id');
        if ($trainingProgramId === null) {
            return null;
        }

        $subjectIds = DB::table('subject_training_program')
            ->where('training_program_id', $trainingProgramId)
            ->pluck('subject_id')
            ->all();

        return $subjectIds === [] ? null : $subjectIds;
    }

    private function resolveClasses(int $trainingBatchId): array
    {
        return TrainingClass::query()
            ->where('training_batch_id', $trainingBatchId)
            ->orderBy('code')
            ->get()
            ->keyBy(fn (TrainingClass $class) => (string) $class->id)
            ->all();
    }
}
