<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\Shared\ScheduleSlotConflictChecker;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\SemesterEvent;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingBatch;
use Modules\Training\Models\TrainingClass;

class UpdateScheduleSemesterHandler
{
    public function handle(UpdateScheduleSemesterRequest $request, $id)
    {
        $plan = Plans::findOrFail($id);
        $validated = $request->validated();

        $semester = (int) $validated['semester'];
        $year = (int) $validated['year'];
        $planStart = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : ($plan->effective_from ? Carbon::parse($plan->effective_from) : $this->defaultSemesterStart($semester, $year));
        $planEnd = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : ($plan->effective_to ? Carbon::parse($plan->effective_to)->endOfDay() : $this->defaultSemesterEnd($semester, $year));

        if ($planEnd->lt($planStart)) {
            throw ValidationException::withMessages([
                'end_date' => 'Ngay ket thuc hoc ky phai lon hon hoac bang ngay bat dau.',
            ]);
        }

        $classMap = $this->resolveClasses(
            $validated['selected_class_ids'] ?? [],
            $validated['class_name'] ?? null,
            $plan->training_batch_id ? (int) $plan->training_batch_id : null
        );

        if ($classMap === []) {
            throw ValidationException::withMessages([
                'selected_class_ids' => 'Phai chon it nhat mot lop hoac nhap lop bo sung.',
            ]);
        }

        $allowedSubjectIds = $this->resolveAllowedSubjectIds($plan->training_batch_id);

        $templateEntries = array_merge(
            $this->parseClassTabRules(
                $validated['class_tab_rules'] ?? [],
                $classMap,
                $allowedSubjectIds
            ),
            $this->parseImportFiles($request, $classMap, $allowedSubjectIds)
        );

        if ($templateEntries === []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => 'Khong co du lieu lich tong quat de cap nhat ke hoach.',
            ]);
        }

        $semesterEvents = array_merge(
            $this->parseGlobalSemesterEvents($validated['global_semester_events'] ?? [], $planStart, $planEnd),
            $this->parseClassSemesterEvents($validated['class_semester_events'] ?? [], $classMap, $planStart, $planEnd)
        );

        $this->validateSemesterEventsWithinPlan($semesterEvents, $planStart, $planEnd);
        $this->validateSemesterEventConflicts($semesterEvents);
        $this->validateTemplateEntries($templateEntries, $planStart, $planEnd);

        // Coverage must be enforced across every class in the plan's training_batch,
        // not just the classes submitted in this edit — otherwise classes left out
        // of the payload silently lose all templates once the old ones are wiped below.
        $batchClassIds = $plan->training_batch_id !== null
            ? TrainingClass::query()
                ->where('training_batch_id', $plan->training_batch_id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all()
            : collect($classMap)->map(fn (TrainingClass $class): int => (int) $class->id)->unique()->values()->all();

        $conflictChecker = new ScheduleSlotConflictChecker();

        $coverageGaps = $conflictChecker->findMonthsWithoutCoverage($templateEntries, $semesterEvents, $batchClassIds, $planStart, $planEnd);
        if ($coverageGaps !== []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => $this->formatCoverageGapMessages($coverageGaps),
            ]);
        }

        $slotConflicts = $conflictChecker->findTemplateEventConflicts($templateEntries, $semesterEvents, $batchClassIds);
        if ($slotConflicts !== []) {
            throw ValidationException::withMessages([
                'class_semester_events' => $slotConflicts,
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

        return redirect()->route('schedule.semester.public', [
            'semester' => $updatedPlan->semester,
            'year' => $updatedPlan->year,
            'className' => $className,
            'training_batch_id' => $updatedPlan->training_batch_id,
        ])->with('success', 'Da cap nhat ke hoach hoc ky va lich tong quat thanh cong.');
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

    private function resolveClasses(array $selectedClassIds, ?string $fallbackClassName, ?int $trainingBatchId): array
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

                $classMap['name:' . $normalized] = TrainingClass::query()->firstOrCreate(
                    ['code' => $normalized],
                    $createData
                );
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

    private function parseClassTabRules(
        array|string|null $rawRules,
        array $classMap,
        ?array $allowedSubjectIds = null
    ): array {
        if ($rawRules === null || $rawRules === '' || $rawRules === []) {
            return [];
        }

        $decoded = is_string($rawRules) ? json_decode($rawRules, true) : $rawRules;
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'class_tab_rules' => 'Du lieu quy tac lich khong hop le.',
            ]);
        }

        $rows = [];

        foreach ($decoded as $classKey => $classRules) {
            if (!array_key_exists((string) $classKey, $classMap) || !is_array($classRules)) {
                continue;
            }

            foreach ($classRules as $rule) {
                if (!is_array($rule) || !$this->ruleHasInput($rule)) {
                    continue;
                }

                $periodFrom = (int) $rule['period_from'];
                $periodTo = (int) $rule['period_to'];
                $daysOfWeek = $this->normalizeWeekdays($rule['weekdays'] ?? []);
                $subjectText = trim((string) ($rule['subject'] ?? ''));
                $content = trim((string) ($rule['content'] ?? '')) ?: null;

                if ($subjectText === '') {
                    throw ValidationException::withMessages([
                        'class_tab_rules' => 'Moi quy tac phai nhap mon hoc.',
                    ]);
                }

                $rows[] = [
                    'class_id' => $classMap[(string) $classKey]->id,
                    'subject_id' => $this->resolveSubjectId($subjectText, $allowedSubjectIds),
                    'day_of_week' => $daysOfWeek[0],
                    'days_of_week' => $daysOfWeek,
                    'session' => $periodTo <= 5 ? 'Sang' : 'Chieu',
                    'period_range' => sprintf('%d-%d', $periodFrom, $periodTo),
                    'description' => $content,
                    'start_date' => Carbon::parse($rule['start_date'])->toDateString(),
                    'end_date' => Carbon::parse($rule['end_date'])->toDateString(),
                ];
            }
        }

        return $rows;
    }

    private function parseGlobalSemesterEvents(array|string|null $rawEvents, Carbon $planStart, Carbon $planEnd): array
    {
        if ($rawEvents === null || $rawEvents === '' || $rawEvents === []) {
            return [];
        }

        $decoded = is_string($rawEvents) ? json_decode($rawEvents, true) : $rawEvents;
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'global_semester_events' => 'Du lieu su kien nghi le khong hop le.',
            ]);
        }

        $rows = [];
        foreach ($decoded as $index => $event) {
            if (!is_array($event)) {
                continue;
            }

            $title = trim((string) ($event['title'] ?? ''));
            $eventType = trim((string) ($event['event_type'] ?? ''));
            $startDate = $event['start_date'] ?? null;
            $endDate = $event['end_date'] ?? null;

            if ($title === '' || $eventType === '' || !$this->isDateValue($startDate) || !$this->isDateValue($endDate)) {
                continue;
            }

            if ($eventType !== 'holiday') {
                throw ValidationException::withMessages([
                    'global_semester_events' => 'Su kien chung chi chap nhan loai nghi le.',
                ]);
            }

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            if ($end->lt($start)) {
                throw ValidationException::withMessages([
                    'global_semester_events' => 'Su kien nghi le co ngay ket thuc nho hon ngay bat dau.',
                ]);
            }

            if ($start->lt($planStart) || $end->gt($planEnd)) {
                throw ValidationException::withMessages([
                    'global_semester_events' => 'Su kien nghi le phai nam trong khoang thoi gian hoc ky.',
                ]);
            }

            $periodFrom = $this->parseOptionalPeriodValue($event['period_from'] ?? null) ?? 1;
            $periodTo = $this->parseOptionalPeriodValue($event['period_to'] ?? null) ?? 9;

            if ($periodTo < $periodFrom) {
                throw ValidationException::withMessages([
                    'global_semester_events' => 'Su kien nghi le co tiet ket thuc nho hon tiet bat dau.',
                ]);
            }

            $rows[] = [
                'class_id' => null,
                'event_type' => $eventType,
                'title' => $title,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'color' => $this->semesterEventColor($eventType),
                'note' => trim((string) ($event['note'] ?? '')) ?: null,
                'sort_order' => is_numeric($event['sort_order'] ?? null) ? (int) $event['sort_order'] : $index,
            ];
        }

        return $rows;
    }

    private function parseClassSemesterEvents(array|string|null $rawEvents, array $classMap, Carbon $planStart, Carbon $planEnd): array
    {
        if ($rawEvents === null || $rawEvents === '' || $rawEvents === []) {
            return [];
        }

        $decoded = is_string($rawEvents) ? json_decode($rawEvents, true) : $rawEvents;
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'class_semester_events' => 'Du lieu su kien cua lop khong hop le.',
            ]);
        }

        $rows = [];

        foreach ($decoded as $classKey => $events) {
            if (!array_key_exists((string) $classKey, $classMap) || !is_array($events)) {
                continue;
            }

            foreach ($events as $index => $event) {
                if (!is_array($event)) {
                    continue;
                }

                $title = trim((string) ($event['title'] ?? ''));
                $eventType = trim((string) ($event['event_type'] ?? ''));
                $startDate = $event['start_date'] ?? null;
                $endDate = $event['end_date'] ?? null;

                if ($title === '' || $eventType === '' || !$this->isDateValue($startDate) || !$this->isDateValue($endDate)) {
                    continue;
                }

                if (!in_array($eventType, ['review', 'exam', 'other'], true)) {
                    throw ValidationException::withMessages([
                        'class_semester_events' => 'Su kien cua lop chi chap nhan loai on thi, thi hoac khac.',
                    ]);
                }

                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();

                if ($end->lt($start)) {
                    throw ValidationException::withMessages([
                        'class_semester_events' => 'Su kien cua lop co ngay ket thuc nho hon ngay bat dau.',
                    ]);
                }

                if ($start->lt($planStart) || $end->gt($planEnd)) {
                    throw ValidationException::withMessages([
                        'class_semester_events' => 'Su kien cua lop phai nam trong khoang thoi gian hoc ky.',
                    ]);
                }

                $periodFrom = $this->parseOptionalPeriodValue($event['period_from'] ?? null) ?? 1;
                $periodTo = $this->parseOptionalPeriodValue($event['period_to'] ?? null) ?? 9;

                if ($periodTo < $periodFrom) {
                    throw ValidationException::withMessages([
                        'class_semester_events' => 'Su kien cua lop co tiet ket thuc nho hon tiet bat dau.',
                    ]);
                }

                $rows[] = [
                    'class_id' => $classMap[(string) $classKey]->id,
                    'event_type' => $eventType,
                    'title' => $title,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'period_from' => $periodFrom,
                    'period_to' => $periodTo,
                    'color' => $this->semesterEventColor($eventType),
                    'note' => trim((string) ($event['note'] ?? '')) ?: null,
                    'sort_order' => is_numeric($event['sort_order'] ?? null) ? (int) $event['sort_order'] : $index,
                ];
            }
        }

        return $rows;
    }

    private function parseImportFiles(
        UpdateScheduleSemesterRequest $request,
        array $classMap,
        ?array $allowedSubjectIds = null
    ): array {
        $rows = [];
        $rawImports = $request->file('import_file', []);

        if ($rawImports === null) {
            return [];
        }

        if ($rawImports instanceof UploadedFile) {
            if (count($classMap) !== 1) {
                throw ValidationException::withMessages([
                    'import_file' => 'Khi gui mot file import duy nhat, chi duoc chon mot lop.',
                ]);
            }

            $imports = [array_key_first($classMap) => $rawImports];
        } elseif (is_array($rawImports)) {
            $imports = $rawImports;
        } else {
            throw ValidationException::withMessages([
                'import_file' => 'File import khong hop le.',
            ]);
        }

        foreach ($imports as $classKey => $importFile) {
            if (!$importFile instanceof UploadedFile) {
                continue;
            }

            if (!array_key_exists((string) $classKey, $classMap)) {
                throw ValidationException::withMessages([
                    'import_file' => "File import khong hop le cho lop '{$classKey}'.",
                ]);
            }

            $handle = fopen($importFile->getRealPath(), 'r');
            if ($handle === false) {
                throw ValidationException::withMessages([
                    'import_file' => "Khong the doc file import cho lop '{$classKey}'.",
                ]);
            }

            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                continue;
            }

            $headerMap = array_flip(array_map(
                fn($header) => trim(strtolower((string) $header)),
                $headers
            ));

            // Required columns
            $requiredColumns = ['class_code', 'period_from', 'period_to', 'subject'];
            foreach ($requiredColumns as $column) {
                if (!array_key_exists($column, $headerMap)) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'import_file' => "File import cho lop '{$classKey}' thieu cot bat buoc '{$column}'.",
                    ]);
                }
            }

            // Optional columns (at least date or start_date must exist)
            $hasDateColumn = array_key_exists('date', $headerMap);
            $hasStartDateColumn = array_key_exists('start_date', $headerMap);

            if (!$hasDateColumn && !$hasStartDateColumn) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'import_file' => "File import cho lop '{$classKey}' phai co cot 'date' hoac 'start_date'.",
                ]);
            }

            $class = $classMap[(string) $classKey];

            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $rowData = [];
                foreach ($headerMap as $column => $index) {
                    $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                }

                // Validate class code
                if (Str::upper((string) $rowData['class_code']) !== Str::upper($class->code)) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'import_file' => "Ma lop trong file ('{$rowData['class_code']}') khong khop voi lop '{$class->code}'.",
                    ]);
                }

                // Determine start date
                $startDateStr = $rowData['start_date'] ?? $rowData['date'] ?? null;
                if (empty($startDateStr) || !strtotime((string) $startDateStr)) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'import_file' => "File import cua lop '{$class->code}' chua ngay bat dau hop le.",
                    ]);
                }
                $startDate = Carbon::parse((string) $startDateStr)->startOfDay();

                // Determine end date (default to start date if not provided)
                $endDateStr = $rowData['end_date'] ?? $rowData['start_date'] ?? $rowData['date'] ?? null;
                if (empty($endDateStr) || !strtotime((string) $endDateStr)) {
                    $endDate = $startDate->copy();
                } else {
                    $endDate = Carbon::parse((string) $endDateStr)->startOfDay();
                }

                // Validate date range
                if ($endDate->lt($startDate)) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'import_file' => "File import cua lop '{$class->code}' co ngay ket thuc nho hon ngay bat dau.",
                    ]);
                }

                // Validate periods
                $periodFrom = is_numeric($rowData['period_from']) ? (int) $rowData['period_from'] : null;
                $periodTo = is_numeric($rowData['period_to']) ? (int) $rowData['period_to'] : null;
                if ($periodFrom === null || $periodTo === null || $periodFrom < 1 || $periodFrom > 9 || $periodTo < 1 || $periodTo > 9 || $periodTo < $periodFrom) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'import_file' => "File import cua lop '{$class->code}' chua tiet khong hop le.",
                    ]);
                }

                // Parse weekdays (nếu có cột weekdays)
                $weekdaysStr = $rowData['weekdays'] ?? $rowData['days_of_week'] ?? null;
                if (!empty($weekdaysStr)) {
                    // Format: "2,3,4,5,6" hoặc "2;3;4;5;6" hoặc "Monday,Tuesday,..."
                    $daysOfWeek = $this->parseWeekdaysFromString($weekdaysStr);
                } else {
                    // Nếu không có, dùng weekday của ngày start_date
                    $daysOfWeek = [$startDate->dayOfWeekIso + 1];
                }

                if ($daysOfWeek === []) {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'import_file' => "File import cua lop '{$class->code}' chua ngay trong tuan hop le.",
                    ]);
                }

                $subjectText = trim((string) ($rowData['subject'] ?? ''));
                $content = trim((string) ($rowData['content'] ?? '')) ?: null;

                if ($subjectText === '') {
                    fclose($handle);
                    throw ValidationException::withMessages([
                        'import_file' => "File import cua lop '{$class->code}' chua mon hoc.",
                    ]);
                }

                $rows[] = [
                    'class_id' => $class->id,
                    'subject_id' => $this->resolveSubjectId($subjectText, $allowedSubjectIds),
                    'day_of_week' => $daysOfWeek[0],
                    'days_of_week' => $daysOfWeek,
                    'session' => $periodTo <= 5 ? 'Sang' : 'Chieu',
                    'period_range' => sprintf('%d-%d', $periodFrom, $periodTo),
                    'description' => $content,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ];
            }

            fclose($handle);
        }

        return $rows;
    }

    private function parseWeekdaysFromString(string $input): array
    {
        if (empty($input)) {
            return [];
        }

        // Split by comma or semicolon
        $parts = preg_split('/[,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY);
        $weekdays = [];

        $dayNameMap = [
            'monday' => 2, 'mon' => 2,
            'tuesday' => 3, 'tue' => 3,
            'wednesday' => 4, 'wed' => 4,
            'thursday' => 5, 'thu' => 5,
            'friday' => 6, 'fri' => 6,
            'saturday' => 7, 'sat' => 7,
            'sunday' => 8, 'sun' => 8,
            'chu nhat' => 8,
            'thu hai' => 2,
            'thu ba' => 3,
            'thu tu' => 4,
            'thu nam' => 5,
            'thu sau' => 6,
            'thu bay' => 7,
        ];

        foreach ($parts as $part) {
            $part = trim(strtolower($part));

            // Try number first (2-8)
            if (is_numeric($part)) {
                $dayNum = (int) $part;
                if ($dayNum >= 2 && $dayNum <= 8) {
                    $weekdays[] = $dayNum;
                }
            } else {
                // Try day name
                if (isset($dayNameMap[$part])) {
                    $weekdays[] = $dayNameMap[$part];
                }
            }
        }

        return collect($weekdays)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function normalizeWeekdays(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $weekdays = is_array($value)
            ? $value
            : preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return collect($weekdays)
            ->filter(fn($item) => is_numeric($item))
            ->map(fn($item) => (int) $item)
            ->filter(fn(int $dow) => $dow >= 2 && $dow <= 8)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function resolveSubjectId(string $subjectText, ?array $allowedSubjectIds = null): int
    {
        $subjectText = trim($subjectText);

        $subject = Subject::query()
            ->where('code', $subjectText)
            ->orWhere('name', $subjectText)
            ->first();

        if (!$subject) {
            throw ValidationException::withMessages([
                'class_tab_rules' => "Mon hoc '{$subjectText}' khong ton tai trong he thong. Vui long tao mon hoc truoc trong danh muc mon hoc.",
            ]);
        }

        if ($allowedSubjectIds !== null && !in_array($subject->id, $allowedSubjectIds, true)) {
            throw ValidationException::withMessages([
                'class_tab_rules' => "Mon hoc '{$subjectText}' khong thuoc chuong trinh dao tao cua khoa dao tao da chon.",
            ]);
        }

        return $subject->id;
    }

    private function validateTemplateEntries(array $entries, Carbon $planStart, Carbon $planEnd): void
    {
        $seenSlots = [];
        $classCodes = TrainingClass::query()
            ->whereIn('id', collect($entries)->pluck('class_id')->unique()->all())
            ->pluck('code', 'id');

        foreach ($entries as $entry) {
            $startDate = Carbon::parse($entry['start_date'])->startOfDay();
            $endDate = Carbon::parse($entry['end_date'])->endOfDay();

            if ($startDate->lt($planStart) || $endDate->gt($planEnd)) {
                throw ValidationException::withMessages([
                    'class_tab_rules' => 'Moi quy tac phai nam trong khoang thoi gian hoc ky.',
                ]);
            }

            if ($endDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    'class_tab_rules' => 'Co quy tac co ngay ket thuc nho hon ngay bat dau.',
                ]);
            }

            if ($this->normalizeWeekdays($entry['days_of_week'] ?? []) === []) {
                throw ValidationException::withMessages([
                    'class_tab_rules' => 'Moi quy tac phai co it nhat mot thu hoc.',
                ]);
            }

            if ($this->parsePeriodRange((string) $entry['period_range']) === []) {
                throw ValidationException::withMessages([
                    'class_tab_rules' => 'Khoang tiet trong quy tac khong hop le.',
                ]);
            }

            $daysOfWeek = $this->normalizeWeekdays($entry['days_of_week'] ?? []);
            $periods = $this->parsePeriodRange((string) $entry['period_range']);
            $classCode = $classCodes->get($entry['class_id'], (string) $entry['class_id']);

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dayOfWeek = $date->dayOfWeekIso + 1;
                if (!in_array($dayOfWeek, $daysOfWeek, true)) {
                    continue;
                }

                foreach ($periods as $period) {
                    $slotKey = implode('|', [
                        $entry['class_id'],
                        $date->toDateString(),
                        $period,
                    ]);

                    if (isset($seenSlots[$slotKey])) {
                        throw ValidationException::withMessages([
                            'class_tab_rules' => 'Lop ' . $classCode . ' bi trung mon trong cung ngay va cung tiet.',
                        ]);
                    }

                    $seenSlots[$slotKey] = true;
                }
            }
        }
    }

    private function createMonthlySchedules(Plans $plan, array $templateModels): array
    {
        $months = [];
        $rangeStart = Carbon::parse($plan->effective_from)->copy()->startOfMonth();
        $rangeEnd = Carbon::parse($plan->effective_to)->copy()->endOfMonth();

        for ($cursor = $rangeStart->copy(); $cursor->lte($rangeEnd); $cursor->addMonth()) {
            $months[] = ['month' => $cursor->month, 'year' => $cursor->year];
        }

        $classIds = collect($templateModels)->pluck('class_id')->unique()->values();
        $classesById = TrainingClass::query()
            ->whereIn('id', $classIds->all())
            ->get()
            ->keyBy('id');

        $hasClassIdColumn = Schema::hasColumn('monthly_schedules', 'class_id');
        $hasDepartmentIdColumn = Schema::hasColumn('monthly_schedules', 'department_id');

        $monthlySchedules = [];
        $created = [];

        foreach ($templateModels as $template) {
            $classId = $template->class_id;
            $trainingClass = $classesById->get($classId);
            $className = $trainingClass?->code ?? 'Unknown';

            foreach ($months as $monthInfo) {
                $key = sprintf('%s|%s|%s', $classId, $monthInfo['year'], $monthInfo['month']);
                if (isset($created[$key])) {
                    $monthlySchedules[$key] = $created[$key];
                    continue;
                }

                $createData = [
                    'plan_id' => $plan->id,
                    'class_name' => $className,
                    'month' => $monthInfo['month'],
                    'year' => $monthInfo['year'],
                    'created_by' => $plan->created_by,
                    'status' => 'draft',
                ];

                if ($hasClassIdColumn) {
                    $createData['class_id'] = $classId;
                }

                if ($hasDepartmentIdColumn) {
                    $createData['department_id'] = $trainingClass?->department_id;
                }

                $monthly = MonthlySchedule::query()->create($createData);
                $created[$key] = $monthly;
                $monthlySchedules[$key] = $monthly;
            }
        }

        return $monthlySchedules;
    }

    private function createScheduleSlots(array $templates, array $monthlySchedules): void
    {
        $subjectNames = Subject::query()
            ->whereIn('id', collect($templates)->pluck('subject_id')->unique()->all())
            ->pluck('name', 'id');
        $createdSlotKeys = [];
        $classCodes = TrainingClass::query()
            ->whereIn('id', collect($templates)->pluck('class_id')->unique()->all())
            ->pluck('code', 'id');

        foreach ($templates as $template) {
            $classId = $template->class_id;
            $daysOfWeek = is_array($template->days_of_week)
                ? $template->days_of_week
                : (json_decode((string) $template->days_of_week, true) ?: [$template->day_of_week]);

            $periods = $this->parsePeriodRange((string) $template->period_range);
            $subjectName = $template->subjects?->name ?? $subjectNames->get($template->subject_id) ?? 'Mon hoc';
            $content = trim((string) $template->description) ?: null;

            $start = Carbon::parse($template->start_date)->startOfDay();
            $end = Carbon::parse($template->end_date)->endOfDay();

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if (!in_array($date->dayOfWeekIso + 1, $daysOfWeek, true)) {
                    continue;
                }

                $monthKey = sprintf('%s|%s|%s', $classId, $date->year, $date->month);
                if (!isset($monthlySchedules[$monthKey])) {
                    continue;
                }

                foreach ($periods as $periodNumber) {
                    $slotKey = implode('|', [$classId, $date->toDateString(), $periodNumber]);
                    if (isset($createdSlotKeys[$slotKey])) {
                        $classCode = $classCodes->get($classId, (string) $classId);
                        throw ValidationException::withMessages([
                            'class_tab_rules' => 'Lop ' . $classCode . ' bi trung mon trong cung ngay va cung tiet.',
                        ]);
                    }

                    $createdSlotKeys[$slotKey] = true;

                    ScheduleSlot::query()->create([
                        'monthly_schedule_id' => $monthlySchedules[$monthKey]->id,
                        'class_id' => $classId,
                        'subject_id' => $template->subject_id,
                        'date' => $date->toDateTimeString(),
                        'day_of_week' => $date->dayOfWeekIso + 1,
                        'period' => (string) $periodNumber,
                        'period_number' => $periodNumber,
                        'subject' => $subjectName,
                        'content' => $content,
                        'slot_status' => 'planned',
                    ]);
                }
            }
        }
    }

    private function parsePeriodRange(string $range): array
    {
        if (preg_match('/^(\\d+)\\s*-\\s*(\\d+)$/', trim($range), $matches)) {
            return range((int) $matches[1], (int) $matches[2]);
        }

        return [];
    }

    private function ruleHasInput(array $rule): bool
    {
        $weekdays = $rule['weekdays'] ?? null;
        $hasWeekdays = is_array($weekdays)
            ? count(array_filter($weekdays, fn($value) => $value !== null && $value !== '')) > 0
            : ($weekdays !== null && $weekdays !== '');

        return $hasWeekdays || collect([
            $rule['start_date'] ?? null,
            $rule['end_date'] ?? null,
            $rule['period_from'] ?? null,
            $rule['period_to'] ?? null,
            $rule['subject'] ?? null,
            $rule['content'] ?? null,
        ])->contains(fn($value) => $value !== null && $value !== '');
    }

    private function parseOptionalPeriodValue(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $period = (int) $value;
        return $period >= 1 && $period <= 9 ? $period : null;
    }

    private function isDateValue(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            Carbon::parse($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function semesterEventColor(string $eventType): string
    {
        return match ($eventType) {
            'holiday' => '#ffedd5',
            'review' => '#dbeafe',
            'exam' => '#fee2e2',
            default => '#ede9fe',
        };
    }

    private function validateSemesterEventsWithinPlan(array $events, Carbon $planStart, Carbon $planEnd): void
    {
        foreach ($events as $event) {
            $start = Carbon::parse($event['start_date'])->startOfDay();
            $end = Carbon::parse($event['end_date'])->endOfDay();

            if ($end->lt($start)) {
                throw ValidationException::withMessages([
                    'class_semester_events' => 'Co su kien hoc ky co ngay ket thuc nho hon ngay bat dau.',
                ]);
            }

            if ($start->lt($planStart) || $end->gt($planEnd)) {
                throw ValidationException::withMessages([
                    'class_semester_events' => 'Su kien hoc ky phai nam trong khoang thoi gian hoc ky.',
                ]);
            }
        }
    }

    private function validateSemesterEventConflicts(array $events): void
    {
        $globalEvents = array_values(array_filter($events, fn (array $event) => ($event['class_id'] ?? null) === null));
        $classEvents = array_values(array_filter($events, fn (array $event) => ($event['class_id'] ?? null) !== null));

        $this->assertNoOverlapWithinGroup($globalEvents, 'global_semester_events', 'Su kien nghi le');

        $classGroups = collect($classEvents)->groupBy(fn (array $event) => (string) ($event['class_id'] ?? ''));
        foreach ($classGroups as $classId => $classGroup) {
            $this->assertNoOverlapWithinGroup(
                $classGroup->values()->all(),
                'class_semester_events',
                'Su kien cua lop ' . $this->classLabelById((int) $classId)
            );
        }

        foreach ($globalEvents as $globalEvent) {
            foreach ($classEvents as $classEvent) {
                if ($this->dateRangesOverlap($globalEvent, $classEvent) && $this->eventPeriodsOverlap($globalEvent, $classEvent)) {
                    $classLabel = $this->classLabelById((int) $classEvent['class_id']);
                    throw ValidationException::withMessages([
                        'global_semester_events' => "Su kien nghi le '{$globalEvent['title']}' bi trung ngay/tiet voi su kien cua lop '{$classLabel}'.",
                    ]);
                }
            }
        }
    }

    private function assertNoOverlapWithinGroup(array $events, string $errorKey, string $labelPrefix): void
    {
        $count = count($events);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($this->dateRangesOverlap($events[$i], $events[$j]) && $this->eventPeriodsOverlap($events[$i], $events[$j])) {
                    throw ValidationException::withMessages([
                        $errorKey => $labelPrefix . " '" . ($events[$i]['title'] ?? 'su kien') . "' bi trung ngay/tiet voi '" . ($events[$j]['title'] ?? 'su kien') . "'.",
                    ]);
                }
            }
        }
    }

    private function eventPeriodsOverlap(array $first, array $second): bool
    {
        $firstFrom = (int) ($first['period_from'] ?? 1);
        $firstTo = (int) ($first['period_to'] ?? 9);
        $secondFrom = (int) ($second['period_from'] ?? 1);
        $secondTo = (int) ($second['period_to'] ?? 9);

        return max($firstFrom, $secondFrom) <= min($firstTo, $secondTo);
    }

    private function classLabelById(int $classId): string
    {
        return TrainingClass::query()->find($classId)?->code ?? (string) $classId;
    }

    /**
     * @param array<int, array{class_id:int, month:int, year:int}> $gaps
     * @return array<int, string>
     */
    private function formatCoverageGapMessages(array $gaps): array
    {
        return collect($gaps)
            ->map(fn (array $gap): string => sprintf(
                'Lop %s thieu du lieu lich (quy tac/su kien) cho thang %d/%d.',
                $this->classLabelById($gap['class_id']),
                $gap['month'],
                $gap['year']
            ))
            ->unique()
            ->values()
            ->all();
    }

    private function dateRangesOverlap(array $first, array $second): bool
    {
        $firstStart = Carbon::parse($first['start_date'])->startOfDay();
        $firstEnd = Carbon::parse($first['end_date'])->endOfDay();
        $secondStart = Carbon::parse($second['start_date'])->startOfDay();
        $secondEnd = Carbon::parse($second['end_date'])->endOfDay();

        return $firstStart->lte($secondEnd) && $secondStart->lte($firstEnd);
    }
}
