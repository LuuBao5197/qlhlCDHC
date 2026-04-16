<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Subject;
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
            $validated['class_name'] ?? null
        );

        if ($classMap === []) {
            throw ValidationException::withMessages([
                'selected_class_ids' => 'Phai chon it nhat mot lop hoac nhap lop bo sung.',
            ]);
        }

        $defaultSubject = trim((string) ($validated['default_subject'] ?? 'Chua xep mon')) ?: 'Chua xep mon';
        $defaultContent = trim((string) ($validated['default_content'] ?? 'Noi dung se cap nhat sau'))
            ?: 'Noi dung se cap nhat sau';

        $templateEntries = array_merge(
            $this->parseClassTabRules(
                $validated['class_tab_rules'] ?? [],
                $classMap,
                $defaultSubject,
                $defaultContent
            ),
            $this->parseImportFiles($request, $classMap, $defaultSubject, $defaultContent)
        );

        if ($templateEntries === []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => 'Khong co du lieu lich tong quat de cap nhat ke hoach.',
            ]);
        }

        $this->validateTemplateEntries($templateEntries, $planStart, $planEnd);

        $updatedPlan = DB::transaction(function () use (
            $request,
            $plan,
            $semester,
            $year,
            $validated,
            $templateEntries,
            $planStart,
            $planEnd,
            $defaultContent
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

            // $monthlySchedules = $this->createMonthlySchedules($plan, $templateModels);
            // $this->createScheduleSlots($templateModels, $monthlySchedules, $defaultContent);

            return $plan;
        });

        $firstClass = reset($classMap);
        $className = $firstClass ? $firstClass->code : null;

        return redirect()->route('schedule.semester.public', [
            'semester' => $updatedPlan->semester,
            'year' => $updatedPlan->year,
            'className' => $className,
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

    private function resolveClasses(array $selectedClassIds, ?string $fallbackClassName): array
    {
        $classMap = [];

        $selectedClassIds = collect($selectedClassIds)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedClassIds->isNotEmpty()) {
            $classes = TrainingClass::query()
                ->whereIn('id', $selectedClassIds->all())
                ->get()
                ->keyBy('id');

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
                $classMap[(string) $existing->id] = $existing;
            } else {
                $classMap['name:' . $normalized] = TrainingClass::query()->firstOrCreate(
                    ['code' => $normalized],
                    ['name' => $normalized, 'status' => 'active']
                );
            }
        }

        return $classMap;
    }

    private function normalizeClassCode(string $className): string
    {
        return Str::upper((string) preg_replace('/[^A-Z0-9_]/', '_', trim($className)));
    }

    private function parseClassTabRules(
        array|string|null $rawRules,
        array $classMap,
        string $defaultSubject,
        string $defaultContent
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
                $subjectText = trim((string) ($rule['subject'] ?? $defaultSubject)) ?: $defaultSubject;
                $content = trim((string) ($rule['content'] ?? $defaultContent)) ?: $defaultContent;

                $rows[] = [
                    'class_id' => $classMap[(string) $classKey]->id,
                    'subject_id' => $this->resolveSubjectId($subjectText),
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

    private function parseImportFiles(
        UpdateScheduleSemesterRequest $request,
        array $classMap,
        string $defaultSubject,
        string $defaultContent
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

                $subjectText = trim((string) ($rowData['subject'] ?? $defaultSubject)) ?: $defaultSubject;
                $content = trim((string) ($rowData['content'] ?? $defaultContent)) ?: $defaultContent;

                $rows[] = [
                    'class_id' => $class->id,
                    'subject_id' => $this->resolveSubjectId($subjectText),
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

    private function resolveSubjectId(string $subjectText): int
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

    private function createScheduleSlots(array $templates, array $monthlySchedules, string $defaultContent): void
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
            $content = trim((string) ($template->description ?: $defaultContent));
            if ($content === '') {
                $content = $defaultContent;
            }

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
}
