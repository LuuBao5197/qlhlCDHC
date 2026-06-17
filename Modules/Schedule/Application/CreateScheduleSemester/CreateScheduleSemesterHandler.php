<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

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
use Modules\Schedule\Models\SemesterEvent;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;

class CreateScheduleSemesterHandler
{
    public function handle(CreateScheduleSemesterRequest $request)
    {
        $validated = $request->validated();

        $semester = (int) $validated['semester'];
        $year = (int) $validated['year'];
        $trainingBatchId = (int) $validated['training_batch_id'];
        $planStart = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : $this->defaultSemesterStart($semester, $year);
        $planEnd = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : $this->defaultSemesterEnd($semester, $year);

        if ($planEnd->lt($planStart)) {
            throw ValidationException::withMessages([
                'end_date' => 'Ngay ket thuc hoc ky phai lon hon hoac bang ngay bat dau.',
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
                'training_batch_id' => 'Khoa dao tao ' . ($duplicatePlan->trainingBatch?->code ?? 'da chon') . ' da co ke hoach hoc ky ' . $semester . ' nam ' . $year . '.',
            ]);
        }

        $classMap = $this->resolveClasses($trainingBatchId);
        if ($classMap === []) {
            throw ValidationException::withMessages([
                'selected_class_ids' => 'Khoa dao tao da chon chua co lop nao.',
            ]);
        }

        $importedData = $this->parseImportFiles($request, $classMap);
        $templateEntries = array_merge(
            $this->parseClassTabRules($validated['class_tab_rules'] ?? [], $classMap),
            $importedData['templates']
        );

        if ($templateEntries === []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => 'Khong co du lieu lich tong quat de tao ke hoach.',
            ]);
        }

        $semesterEvents = array_merge(
            $this->parseGlobalSemesterEvents($validated['global_semester_events'] ?? [], $planStart, $planEnd),
            $this->parseClassSemesterEvents($validated['class_semester_events'] ?? [], $classMap, $planStart, $planEnd),
            $importedData['events']
        );

        $this->validateSemesterEventsWithinPlan($semesterEvents, $planStart, $planEnd);
        $this->validateSemesterEventConflicts($semesterEvents);

        $this->validateTemplateEntries($templateEntries, $planStart, $planEnd);

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
            $plan = Plans::query()->create([
                'training_batch_id' => $trainingBatchId,
                'name' => sprintf('Ke hoach hoc ky %d - %d', $semester, $year),
                'semester' => $semester,
                'year' => $year,
                'description' => $validated['description'] ?? null,
                'effective_from' => $planStart->toDateString(),
                'effective_to' => $planEnd->toDateString(),
                'status' => 'draft',
                'current_step' => 'draft',
                'created_by' => $request->user()?->id,
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

        $firstClass = reset($classMap);
        $className = $firstClass ? $firstClass->code : null;

        return redirect()->route('schedule.semester.public', [
            'semester' => $plan->semester,
            'year' => $plan->year,
            'className' => $className,
            'training_batch_id' => $plan->training_batch_id,
        ])->with('success', 'Da tao ke hoach hoc ky va lich tong quat thanh cong.');
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

    private function resolveClasses(int $trainingBatchId): array
    {
        return TrainingClass::query()
            ->where('training_batch_id', $trainingBatchId)
            ->orderBy('code')
            ->get()
            ->keyBy(fn (TrainingClass $class) => (string) $class->id)
            ->all();
    }

    private function parseClassTabRules(array|string|null $rawRules, array $classMap): array
    {
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

    private function parseImportFiles(CreateScheduleSemesterRequest $request, array $classMap): array
    {
        $parsed = [
            'templates' => [],
            'events' => [],
        ];
        $rawImports = $request->file('import_file', []);

        if ($rawImports === null) {
            return $parsed;
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

            $sheetRows = $this->readImportRows($importFile);
            if ($sheetRows === []) {
                continue;
            }

            $headers = array_shift($sheetRows);
            if (!is_array($headers) || $headers === []) {
                continue;
            }

            $headerMap = array_flip(array_map(
                fn ($header) => $this->normalizeImportHeader($header),
                $headers
            ));

            foreach (['class_code', 'period_from', 'period_to'] as $column) {
                if (!array_key_exists($column, $headerMap)) {
                    throw ValidationException::withMessages([
                        'import_file' => "File import cho lop '{$classKey}' thieu cot bat buoc '{$column}'.",
                    ]);
                }
            }

            if (!array_key_exists('date', $headerMap) && !array_key_exists('start_date', $headerMap)) {
                throw ValidationException::withMessages([
                    'import_file' => "File import cho lop '{$classKey}' phai co cot 'date' hoac 'start_date'.",
                ]);
            }

            $class = $classMap[(string) $classKey];

            foreach ($sheetRows as $rowIndex => $row) {
                if (!is_array($row) || count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $rowData = [];
                foreach ($headerMap as $column => $index) {
                    $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                }

                if (Str::upper((string) ($rowData['class_code'] ?? '')) !== Str::upper($class->code)) {
                    throw ValidationException::withMessages([
                        'import_file' => "Ma lop trong file ('{$rowData['class_code']}') khong khop voi lop '{$class->code}'.",
                    ]);
                }

                $rowType = strtolower(trim((string) ($rowData['row_type'] ?? 'rule')));
                if (!in_array($rowType, ['rule', 'event'], true)) {
                    throw ValidationException::withMessages([
                        'import_file' => "Dong #" . ($rowIndex + 2) . " cua lop '{$class->code}' co row_type khong hop le.",
                    ]);
                }

                $startDateStr = $rowData['start_date'] ?? $rowData['date'] ?? null;
                if (empty($startDateStr) || !strtotime((string) $startDateStr)) {
                    throw ValidationException::withMessages([
                        'import_file' => "Dong #" . ($rowIndex + 2) . " cua lop '{$class->code}' chua ngay bat dau hop le.",
                    ]);
                }

                $startDate = Carbon::parse((string) $startDateStr)->startOfDay();
                $endDateStr = $rowData['end_date'] ?? $rowData['start_date'] ?? $rowData['date'] ?? null;
                $endDate = empty($endDateStr) || !strtotime((string) $endDateStr)
                    ? $startDate->copy()
                    : Carbon::parse((string) $endDateStr)->startOfDay();

                if ($endDate->lt($startDate)) {
                    throw ValidationException::withMessages([
                        'import_file' => "Dong #" . ($rowIndex + 2) . " cua lop '{$class->code}' co ngay ket thuc nho hon ngay bat dau.",
                    ]);
                }

                $periodFrom = $this->parseOptionalPeriodValue($rowData['period_from'] ?? null);
                $periodTo = $this->parseOptionalPeriodValue($rowData['period_to'] ?? null);
                if ($periodFrom === null || $periodTo === null || $periodTo < $periodFrom) {
                    throw ValidationException::withMessages([
                        'import_file' => "Dong #" . ($rowIndex + 2) . " cua lop '{$class->code}' chua tiet khong hop le.",
                    ]);
                }

                if ($rowType === 'event') {
                    $eventType = strtolower(trim((string) ($rowData['event_type'] ?? '')));
                    $title = trim((string) ($rowData['title'] ?? ''));

                    if (!in_array($eventType, ['review', 'exam', 'other'], true)) {
                        throw ValidationException::withMessages([
                            'import_file' => "Dong event #" . ($rowIndex + 2) . " cua lop '{$class->code}' co event_type khong hop le.",
                        ]);
                    }

                    if ($title === '') {
                        throw ValidationException::withMessages([
                            'import_file' => "Dong event #" . ($rowIndex + 2) . " cua lop '{$class->code}' phai nhap title.",
                        ]);
                    }

                    $parsed['events'][] = [
                        'class_id' => $class->id,
                        'event_type' => $eventType,
                        'title' => $title,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'period_from' => $periodFrom,
                        'period_to' => $periodTo,
                        'color' => $this->semesterEventColor($eventType),
                        'note' => trim((string) ($rowData['note'] ?? $rowData['content'] ?? '')) ?: null,
                        'sort_order' => $rowIndex,
                    ];

                    continue;
                }

                $weekdaysStr = $rowData['weekdays'] ?? $rowData['days_of_week'] ?? null;
                $daysOfWeek = !empty($weekdaysStr)
                    ? $this->parseWeekdaysFromString((string) $weekdaysStr)
                    : [$startDate->dayOfWeekIso + 1];

                if ($daysOfWeek === []) {
                    throw ValidationException::withMessages([
                        'import_file' => "Dong rule #" . ($rowIndex + 2) . " cua lop '{$class->code}' chua ngay trong tuan hop le.",
                    ]);
                }

                $subjectText = trim((string) ($rowData['subject'] ?? ''));
                if ($subjectText === '') {
                    throw ValidationException::withMessages([
                        'import_file' => "Dong rule #" . ($rowIndex + 2) . " cua lop '{$class->code}' phai nhap subject.",
                    ]);
                }

                $parsed['templates'][] = [
                    'class_id' => $class->id,
                    'subject_id' => $this->resolveSubjectId($subjectText),
                    'day_of_week' => $daysOfWeek[0],
                    'days_of_week' => $daysOfWeek,
                    'session' => $periodTo <= 5 ? 'Sang' : 'Chieu',
                    'period_range' => sprintf('%d-%d', $periodFrom, $periodTo),
                    'description' => trim((string) ($rowData['content'] ?? '')) ?: null,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ];
            }
        }

        return $parsed;
    }

    private function readImportRows(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return match ($extension) {
            'xlsx' => $this->readXlsxRows($file->getRealPath()),
            default => $this->readCsvRows($file->getRealPath()),
        };
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'import_file' => 'Khong the doc file import.',
            ]);
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'import_file' => 'Khong the mo file Excel import.',
            ]);
        }

        $sharedStrings = $this->parseXlsxSharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: null);
        $dateStyles = $this->parseXlsxDateStyleIndexes($zip->getFromName('xl/styles.xml') ?: null);
        $sheetPath = $this->firstWorksheetPath($zip);
        $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;
        $zip->close();

        if ($sheetXml === false || $sheetXml === null) {
            throw ValidationException::withMessages([
                'import_file' => 'File Excel import khong co du lieu sheet hop le.',
            ]);
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false || !isset($xml->sheetData)) {
            throw ValidationException::withMessages([
                'import_file' => 'Khong the doc du lieu trong file Excel import.',
            ]);
        }

        $rows = [];

        foreach ($xml->sheetData->row as $rowNode) {
            $row = [];

            foreach ($rowNode->c as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnIndexFromReference($reference);
                $row[$columnIndex] = $this->parseXlsxCellValue($cell, $sharedStrings, $dateStyles);
            }

            if ($row === []) {
                continue;
            }

            ksort($row);
            $normalized = [];
            $maxIndex = max(array_keys($row));
            for ($i = 0; $i <= $maxIndex; $i++) {
                $normalized[] = $row[$i] ?? '';
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    private function parseXlsxSharedStrings(?string $xml): array
    {
        if ($xml === null || $xml === '') {
            return [];
        }

        $parsed = @simplexml_load_string($xml);
        if ($parsed === false) {
            return [];
        }

        $values = [];
        foreach ($parsed->si as $item) {
            $text = '';

            if (isset($item->t)) {
                $text = (string) $item->t;
            } elseif (isset($item->r)) {
                foreach ($item->r as $run) {
                    $text .= (string) ($run->t ?? '');
                }
            }

            $values[] = $text;
        }

        return $values;
    }

    private function parseXlsxDateStyleIndexes(?string $xml): array
    {
        if ($xml === null || $xml === '') {
            return [];
        }

        $parsed = @simplexml_load_string($xml);
        if ($parsed === false || !isset($parsed->cellXfs)) {
            return [];
        }

        $customFormats = [];
        if (isset($parsed->numFmts)) {
            foreach ($parsed->numFmts->numFmt as $numFmt) {
                $customFormats[(int) $numFmt['numFmtId']] = strtolower((string) $numFmt['formatCode']);
            }
        }

        $dateStyles = [];
        foreach ($parsed->cellXfs->xf as $index => $xf) {
            $numFmtId = (int) ($xf['numFmtId'] ?? 0);
            $formatCode = $customFormats[$numFmtId] ?? null;

            if ($this->isDateNumFmtId($numFmtId, $formatCode)) {
                $dateStyles[(int) $index] = true;
            }
        }

        return $dateStyles;
    }

    private function isDateNumFmtId(int $numFmtId, ?string $formatCode): bool
    {
        if (in_array($numFmtId, [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47], true)) {
            return true;
        }

        if ($formatCode === null) {
            return false;
        }

        return preg_match('/[dmyhs]/', $formatCode) === 1;
    }

    private function firstWorksheetPath(\ZipArchive $zip): ?string
    {
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookRelsXml !== false && $workbookRelsXml !== null) {
            $rels = @simplexml_load_string($workbookRelsXml);
            if ($rels !== false) {
                foreach ($rels->Relationship as $relationship) {
                    $target = (string) ($relationship['Target'] ?? '');
                    if (str_contains($target, 'worksheets/')) {
                        return 'xl/' . ltrim($target, '/');
                    }
                }
            }
        }

        return $zip->locateName('xl/worksheets/sheet1.xml') !== false
            ? 'xl/worksheets/sheet1.xml'
            : null;
    }

    private function parseXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings, array $dateStyles): string
    {
        $type = (string) ($cell['t'] ?? '');
        $styleIndex = isset($cell['s']) ? (int) $cell['s'] : null;
        $value = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        if ($type === 'b') {
            return $value === '1' ? '1' : '0';
        }

        if ($styleIndex !== null && isset($dateStyles[$styleIndex]) && is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $value))->toDateString();
        }

        return trim($value);
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        if ($letters === '') {
            return 0;
        }

        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private function normalizeImportHeader(mixed $header): string
    {
        $value = strtolower(trim((string) $header));
        return ltrim($value, "\xEF\xBB\xBF");
    }

    private function parseWeekdaysFromString(string $input): array
    {
        if (empty($input)) {
            return [];
        }

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

            if (is_numeric($part)) {
                $dayNum = (int) $part;
                if ($dayNum >= 2 && $dayNum <= 8) {
                    $weekdays[] = $dayNum;
                }
            } elseif (isset($dayNameMap[$part])) {
                $weekdays[] = $dayNameMap[$part];
            }
        }

        return collect($weekdays)->unique()->sort()->values()->all();
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
            ->filter(fn ($item) => is_numeric($item))
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $dow) => $dow >= 2 && $dow <= 8)
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

    private function parsePeriodRange(string $range): array
    {
        if (preg_match('/^(\\d+)\\s*-\\s*(\\d+)$/', trim($range), $matches)) {
            return range((int) $matches[1], (int) $matches[2]);
        }

        return [];
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

    private function parseOptionalPeriodValue(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $period = (int) $value;
        return $period >= 1 && $period <= 9 ? $period : null;
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

    private function ruleHasInput(array $rule): bool
    {
        $weekdays = $rule['weekdays'] ?? null;
        $hasWeekdays = is_array($weekdays)
            ? count(array_filter($weekdays, fn ($value) => $value !== null && $value !== '')) > 0
            : ($weekdays !== null && $weekdays !== '');

        return $hasWeekdays || collect([
            $rule['start_date'] ?? null,
            $rule['end_date'] ?? null,
            $rule['period_from'] ?? null,
            $rule['period_to'] ?? null,
            $rule['subject'] ?? null,
            $rule['content'] ?? null,
        ])->contains(fn ($value) => $value !== null && $value !== '');
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

    private function normalizeSemesterEventsForPersistence(array $events): array
    {
        return array_values(array_filter($events, fn ($event) => is_array($event)));
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
                    'class_semester_events' => 'Su kien import phai nam trong khoang thoi gian hoc ky.',
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

    private function dateRangesOverlap(array $first, array $second): bool
    {
        $firstStart = Carbon::parse($first['start_date'])->startOfDay();
        $firstEnd = Carbon::parse($first['end_date'])->endOfDay();
        $secondStart = Carbon::parse($second['start_date'])->startOfDay();
        $secondEnd = Carbon::parse($second['end_date'])->endOfDay();

        return $firstStart->lte($secondEnd) && $secondStart->lte($firstEnd);
    }

    private function weekdaysOverlap(array $first, array $second): bool
    {
        $firstDays = $this->normalizeWeekdays($first['days_of_week'] ?? []);
        $secondDays = $this->normalizeWeekdays($second['days_of_week'] ?? []);

        return array_intersect($firstDays, $secondDays) !== [];
    }

    private function periodsOverlap(array $first, array $second): bool
    {
        $firstPeriods = $this->parsePeriodRange((string) $first['period_range']);
        $secondPeriods = $this->parsePeriodRange((string) $second['period_range']);

        return array_intersect($firstPeriods, $secondPeriods) !== [];
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
}
