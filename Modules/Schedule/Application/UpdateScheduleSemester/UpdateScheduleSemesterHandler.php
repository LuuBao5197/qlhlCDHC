<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\Shared\ScheduleSemesterAnalyzer;
use Modules\Schedule\Application\Shared\ScheduleValidationReport;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\SemesterEvent;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\TrainingBatch;
use Modules\Training\Models\TrainingClass;

class UpdateScheduleSemesterHandler
{
    public function __construct(
        private ScheduleSemesterAnalyzer $analyzer
    ) {}

    /**
     * Runs every validation phase against the submitted input and records all
     * problems in $report. With $persist = false no row is written (a class typed
     * into "class_name" that does not exist yet is represented by an unsaved
     * model), so the form request can call it while validating.
     *
     * @return array{templates: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>, classMap: array<string, TrainingClass>, planStart: Carbon, planEnd: Carbon}
     */
    public function analyze(UpdateScheduleSemesterRequest $request, Plans $plan, ScheduleValidationReport $report, bool $persist): array
    {
        $semester = (int) $request->input('semester');
        $year = (int) $request->input('year');

        $planStart = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()
            : ($plan->effective_from ? Carbon::parse($plan->effective_from) : $this->defaultSemesterStart($semester, $year));
        $planEnd = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()
            : ($plan->effective_to ? Carbon::parse($plan->effective_to)->endOfDay() : $this->defaultSemesterEnd($semester, $year));

        $empty = ['templates' => [], 'events' => [], 'classMap' => [], 'planStart' => $planStart, 'planEnd' => $planEnd];

        if ($planEnd->lt($planStart)) {
            return $empty;
        }

        try {
            $classMap = $this->resolveClasses(
                (array) $request->input('selected_class_ids', []),
                $request->input('class_name'),
                $plan->training_batch_id ? (int) $plan->training_batch_id : null,
                $persist
            );
        } catch (ValidationException $e) {
            $report->mergeException($e);

            return $empty;
        }

        // An empty class list is reported by the form request itself.
        if ($classMap === []) {
            return $empty;
        }

        // Coverage must be enforced across every class in the plan's training_batch,
        // not just the classes submitted in this edit - otherwise classes left out
        // of the payload silently lose all templates once the old ones are wiped.
        $batchClassIds = $plan->training_batch_id !== null
            ? TrainingClass::query()
                ->where('training_batch_id', $plan->training_batch_id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all()
            : collect($classMap)
                ->filter(fn (TrainingClass $class): bool => $class->id !== null)
                ->map(fn (TrainingClass $class): int => (int) $class->id)
                ->unique()
                ->values()
                ->all();

        $result = $this->analyzer->analyze(
            $report,
            $request->input('class_tab_rules', []),
            $request->input('global_semester_events', []),
            $request->input('class_semester_events', []),
            $request->file('import_file', []),
            $classMap,
            $this->resolveAllowedSubjectIds($plan->training_batch_id),
            $planStart,
            $planEnd,
            $batchClassIds
        );

        return $result + ['classMap' => $classMap, 'planStart' => $planStart, 'planEnd' => $planEnd];
    }

    public function handle(UpdateScheduleSemesterRequest $request, $id)
    {
        $plan = Plans::findOrFail($id);
        $validated = $request->validated();

        $semester = (int) $validated['semester'];
        $year = (int) $validated['year'];

        $report = new ScheduleValidationReport();
        $analysis = $this->analyze($request, $plan, $report, true);
        $report->throwIfFailed();

        $planStart = $analysis['planStart'];
        $planEnd = $analysis['planEnd'];
        $classMap = $analysis['classMap'];
        $templateEntries = $analysis['templates'];
        $semesterEvents = $analysis['events'];

        if ($classMap === []) {
            throw ValidationException::withMessages([
                'selected_class_ids' => 'Phải chọn ít nhất một lớp hoặc nhập lớp bổ sung.',
            ]);
        }

        if ($templateEntries === []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => 'Không có dữ liệu lịch tổng quát để cập nhật kế hoạch.',
            ]);
        }

        $updatedPlan = DB::transaction(function () use (
            $request,
            $plan,
            $semester,
            $year,
            $validated,
            $templateEntries,
            $semesterEvents,
            $planStart,
            $planEnd
        ) {
            // Update plan master record
            $plan->update([
                'name' => sprintf('Ke hoach hoc ky %d - %d', $semester, $year),
                'semester' => $semester,
                'year' => $year,
                'description' => $validated['description'] ?? null,
                'effective_from' => $planStart->toDateString(),
                'effective_to' => $planEnd->toDateString(),
            ]);

            // Delete old plan templates
            PlanTemplates::where('plan_id', $plan->id)->delete();
            SemesterEvent::where('plan_id', $plan->id)->delete();

            // Delete old monthly schedules and slots
            foreach ($plan->monthlySchedules as $monthly) {
                ScheduleSlot::where('monthly_schedule_id', $monthly->id)->delete();
            }
            MonthlySchedule::where('plan_id', $plan->id)->delete();

            // Create new templates
            $templateModels = [];
            foreach ($templateEntries as $entry) {
                $templateModels[] = PlanTemplates::query()->create(
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

            // $monthlySchedules = $this->createMonthlySchedules($plan, $templateModels);
            // $this->createScheduleSlots($templateModels, $monthlySchedules);

            return $plan;
        });

        $firstClass = reset($classMap);
        $className = $firstClass ? $firstClass->code : null;

        $redirectUrl = route('schedule.semester.public', [
            'semester' => $updatedPlan->semester,
            'year' => $updatedPlan->year,
            'className' => $className,
            'training_batch_id' => $updatedPlan->training_batch_id,
        ]);
        $successMessage = 'Đã cập nhật kế hoạch học kỳ và lịch tổng quát thành công.';

        if ($request->expectsJson()) {
            session()->flash('success', $successMessage);

            return response()->json(['redirect' => $redirectUrl]);
        }

        return redirect($redirectUrl)->with('success', $successMessage);
    }

    private function defaultSemesterStart(int $semester, int $year): Carbon
    {
        return $semester === 1 ? Carbon::parse("{$year}-01-01") : Carbon::parse("{$year}-07-01");
    }

    private function defaultSemesterEnd(int $semester, int $year): Carbon
    {
        return $semester === 1
            ? Carbon::parse("{$year}-06-30")->endOfDay()
            : Carbon::parse("{$year}-12-31")->endOfDay();
    }

    private function resolveClasses(array $selectedClassIds, ?string $fallbackClassName, ?int $trainingBatchId, bool $persist): array
    {
        $classMap = [];

        $selectedClassIds = collect($selectedClassIds)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedClassIds->isNotEmpty()) {
            $classesQuery = TrainingClass::query()
                ->whereIn('id', $selectedClassIds->all());

            if ($trainingBatchId !== null) {
                $classesQuery->where('training_batch_id', $trainingBatchId);
            }

            $classes = $classesQuery->get()->keyBy('id');

            foreach ($selectedClassIds as $classId) {
                if ($classes->has($classId)) {
                    $classMap[(string) $classId] = $classes->get($classId);
                }
            }
        }

        if ($fallbackClassName !== null && trim($fallbackClassName) !== '') {
            $normalized = $this->normalizeClassCode($fallbackClassName);
            $existing = TrainingClass::query()->where('code', $normalized)->first();

            if ($existing) {
                if ($trainingBatchId !== null && (int) $existing->training_batch_id !== $trainingBatchId) {
                    throw ValidationException::withMessages([
                        'class_name' => "Lop '{$normalized}' khong thuoc khoa dao tao cua ke hoach.",
                    ]);
                }

                $classMap[(string) $existing->id] = $existing;
            } else {
                $createData = ['name' => $normalized, 'status' => 'active'];
                if ($trainingBatchId !== null) {
                    $createData['training_batch_id'] = $trainingBatchId;
                }

                $classMap['name:' . $normalized] = $persist
                    ? TrainingClass::query()->firstOrCreate(['code' => $normalized], $createData)
                    : (new TrainingClass())->forceFill(['code' => $normalized] + $createData);
            }
        }

        return $classMap;
    }

    /**
     * @return array<int, int>|null Null means no restriction (program has not configured a subject list yet).
     */
    private function resolveAllowedSubjectIds(?int $trainingBatchId): ?array
    {
        if ($trainingBatchId === null) {
            return null;
        }

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

    private function normalizeClassCode(string $className): string
    {
        return Str::upper((string) preg_replace('/[^A-Z0-9_]/', '_', trim($className)));
    }
}
