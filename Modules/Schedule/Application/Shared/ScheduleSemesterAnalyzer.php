<?php

namespace Modules\Schedule\Application\Shared;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;

/**
 * Shared validation/parsing pipeline for creating and updating a semester plan.
 *
 * Every phase (manual rules, import files, semester events, template overlap,
 * month coverage, template/event conflicts) always runs and records problems
 * into a ScheduleValidationReport. Nothing aborts at the first error, so the
 * caller can show the complete list in one response.
 */
class ScheduleSemesterAnalyzer
{
    /**
     * @param array<string, TrainingClass> $classMap class key => class (key is the class id, or "name:CODE" for a class not saved yet)
     * @param array<int, int> $coverageClassIds classes that must be covered month by month
     * @return array{templates: array<int, array<string, mixed>>, events: array<int, array<string, mixed>>}
     */
    public function analyze(
        ScheduleValidationReport $report,
        mixed $rawRules,
        mixed $rawGlobalEvents,
        mixed $rawClassEvents,
        mixed $importFiles,
        array $classMap,
        ?array $allowedSubjectIds,
        Carbon $planStart,
        Carbon $planEnd,
        array $coverageClassIds
    ): array {
        $importedData = $this->parseImportFiles($importFiles, $classMap, $planStart, $planEnd, $allowedSubjectIds, $report);

        $templateEntries = array_merge(
            $this->parseClassTabRules($rawRules, $classMap, $allowedSubjectIds, $report),
            $importedData['templates']
        );

        $semesterEvents = array_merge(
            $this->parseGlobalSemesterEvents($rawGlobalEvents, $planStart, $planEnd, $report),
            $this->parseClassSemesterEvents($rawClassEvents, $classMap, $planStart, $planEnd, $report),
            $importedData['events']
        );

        $this->validateSemesterEventsWithinPlan($semesterEvents, $planStart, $planEnd, $report);
        $this->validateSemesterEventConflicts($semesterEvents, $report);

        $classCodes = [];
        foreach ($classMap as $class) {
            if ($class->id !== null) {
                $classCodes[(int) $class->id] = $class->code;
            }
        }

        $this->validateTemplateEntries($templateEntries, $planStart, $planEnd, $classCodes, $report);

        $conflictChecker = new ScheduleSlotConflictChecker();

        // A class with an already-reported problem would only produce misleading
        // "missing month" follow-ups, so it is left out of the coverage check.
        $coverageGaps = $conflictChecker->findMonthsWithoutCoverage(
            $templateEntries,
            $semesterEvents,
            array_values(array_filter($coverageClassIds, fn ($id) => ! $report->isClassFlagged($id))),
            $planStart,
            $planEnd
        );
        $this->reportCoverageGaps($coverageGaps, $report);

        foreach ($conflictChecker->findTemplateEventConflicts($templateEntries, $semesterEvents, $coverageClassIds, $classCodes) as $conflict) {
            $report->add('class_semester_events', $conflict);
        }

        return ['templates' => $templateEntries, 'events' => $semesterEvents];
    }
    private function parseClassTabRules(mixed $rawRules, array $classMap, ?array $allowedSubjectIds, ScheduleValidationReport $report): array
    {
        if ($rawRules === null || $rawRules === '' || $rawRules === []) {
            return [];
        }

        $decoded = is_string($rawRules) ? json_decode($rawRules, true) : $rawRules;
        if (!is_array($decoded)) {
            $report->add('class_tab_rules', 'Dữ liệu quy tắc lịch không hợp lệ.');

            return [];
        }

        $rows = [];

        foreach ($decoded as $classKey => $classRules) {
            if (!array_key_exists((string) $classKey, $classMap) || !is_array($classRules)) {
                continue;
            }

            $class = $classMap[(string) $classKey];

            foreach ($classRules as $index => $rule) {
                if (!is_array($rule) || !$this->ruleHasInput($rule)) {
                    continue;
                }

                // Structural problems (missing subject/dates/weekdays/periods) are
                // reported with the rule number by the form request; here the rule is
                // only dropped, and its class flagged so follow-up checks skip it.
                if (!$this->ruleIsStructurallyValid($rule)) {
                    $report->flagClass($classKey);
                    continue;
                }

                $ruleNumber = (int) $index + 1;
                $subjectText = trim((string) $rule['subject']);

                $subjectId = $report->attempt(
                    fn () => $this->resolveSubjectId($subjectText, $allowedSubjectIds),
                    $classKey,
                    "Lớp '{$class->code}', dòng quy tắc #{$ruleNumber}: "
                );
                if ($subjectId === null) {
                    continue;
                }

                $periodFrom = (int) $rule['period_from'];
                $periodTo = (int) $rule['period_to'];
                $daysOfWeek = $this->normalizeWeekdays($rule['weekdays'] ?? []);

                $rows[] = [
                    'class_id' => $class->id,
                    'class_key' => (string) $classKey,
                    'subject_id' => $subjectId,
                    'subject_label' => $subjectText,
                    'source' => "Dòng quy tắc #{$ruleNumber} của lớp '{$class->code}'",
                    'day_of_week' => $daysOfWeek[0],
                    'days_of_week' => $daysOfWeek,
                    'session' => $periodTo <= 5 ? 'Sang' : 'Chieu',
                    'period_range' => sprintf('%d-%d', $periodFrom, $periodTo),
                    'description' => trim((string) ($rule['content'] ?? '')) ?: null,
                    'start_date' => Carbon::parse($rule['start_date'])->toDateString(),
                    'end_date' => Carbon::parse($rule['end_date'])->toDateString(),
                ];
            }
        }

        return $rows;
    }

    private function ruleIsStructurallyValid(array $rule): bool
    {
        if (trim((string) ($rule['subject'] ?? '')) === ''
            || !$this->isDateValue($rule['start_date'] ?? null)
            || !$this->isDateValue($rule['end_date'] ?? null)
            || Carbon::parse($rule['end_date'])->lt(Carbon::parse($rule['start_date']))
            || $this->normalizeWeekdays($rule['weekdays'] ?? []) === []) {
            return false;
        }

        $periodFrom = $this->parseOptionalPeriodValue($rule['period_from'] ?? null);
        $periodTo = $this->parseOptionalPeriodValue($rule['period_to'] ?? null);

        return $periodFrom !== null && $periodTo !== null && $periodTo >= $periodFrom;
    }

    /**
     * Parses every uploaded import file and returns the resulting rule/event
     * rows. Row-level problems never abort the whole import: each bad row is
     * skipped and its message (with an exact line number and class) is
     * recorded in the report, so the user gets a full list of everything
     * wrong across every file in one submission instead of having to fix and
     * resubmit one error at a time. Only structural issues that make a file
     * impossible to interpret (unreadable file, missing header row/columns)
     * stop that file early - remaining files are still processed.
     */
    private function parseImportFiles(mixed $rawImports, array $classMap, Carbon $planStart, Carbon $planEnd, ?array $allowedSubjectIds, ScheduleValidationReport $report): array
    {
        $parsed = [
            'templates' => [],
            'events' => [],
        ];
        $currentClassKey = null;
        $fail = function (string $message) use ($report, &$currentClassKey): void {
            $report->add('import_file', $message, $currentClassKey);
        };

        if ($rawImports === null || $rawImports === []) {
            return $parsed;
        }

        if ($rawImports instanceof UploadedFile) {
            if (count($classMap) !== 1) {
                $fail('Khi gửi một file import duy nhất, chỉ được chọn một lớp.');

                return $parsed;
            }

            $imports = [array_key_first($classMap) => $rawImports];
        } elseif (is_array($rawImports)) {
            $imports = $rawImports;
        } else {
            $fail('File import không hợp lệ.');

            return $parsed;
        }

        foreach ($imports as $classKey => $importFile) {
            $currentClassKey = null;

            if (!$importFile instanceof UploadedFile) {
                continue;
            }

            if (!array_key_exists((string) $classKey, $classMap)) {
                $fail("File import không hợp lệ cho lớp '{$classKey}'.");
                continue;
            }

            $class = $classMap[(string) $classKey];
            $currentClassKey = (string) $classKey;

            try {
                $sheetRows = $this->readImportRows($importFile);
            } catch (ValidationException $e) {
                $fail("File import của lớp '{$class->code}': " . $this->firstValidationMessage($e));
                continue;
            }

            if ($sheetRows === []) {
                continue;
            }

            $headers = array_shift($sheetRows);
            if (!is_array($headers) || $headers === []) {
                $fail("File import của lớp '{$class->code}' không có dòng tiêu đề hợp lệ.");
                continue;
            }

            $headerMap = array_flip(array_map(
                fn ($header) => $this->importFileReader()->normalizeHeader($header),
                $headers
            ));

            $missingColumns = array_values(array_filter(
                ['class_code', 'period_from', 'period_to'],
                fn (string $column) => !array_key_exists($column, $headerMap)
            ));
            if ($missingColumns !== []) {
                $fail("File import của lớp '{$class->code}' thiếu cột bắt buộc: " . implode(', ', $missingColumns) . '.');
                continue;
            }

            if (!array_key_exists('date', $headerMap) && !array_key_exists('start_date', $headerMap)) {
                $fail("File import của lớp '{$class->code}' phải có cột 'date' hoặc 'start_date'.");
                continue;
            }

            // A file prepared for another class would otherwise produce one error per row.
            // Report it once, naming the class codes it actually contains.
            $classCodeIndex = $headerMap['class_code'];
            $foundCodes = [];
            foreach ($sheetRows as $row) {
                $code = is_array($row) ? trim((string) ($row[$classCodeIndex] ?? '')) : '';
                if ($code !== '') {
                    $foundCodes[Str::upper($code)] = $code;
                }
            }
            if ($foundCodes !== [] && !array_key_exists(Str::upper($class->code), $foundCodes)) {
                $fail("File import đang chọn ở tab lớp '{$class->code}' nhưng nội dung file thuộc lớp: " . implode(', ', array_values($foundCodes)) . ". Hãy chọn đúng file của lớp '{$class->code}'.");
                continue;
            }

            foreach ($sheetRows as $rowIndex => $row) {
                if (!is_array($row) || count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $lineLabel = "Dòng #" . ($rowIndex + 2) . " (file lớp '{$class->code}')";

                $rowData = [];
                foreach ($headerMap as $column => $index) {
                    $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                }

                if (Str::upper((string) ($rowData['class_code'] ?? '')) !== Str::upper($class->code)) {
                    $fail("{$lineLabel}: mã lớp trong file ('{$rowData['class_code']}') không khớp với lớp đang import ('{$class->code}').");
                    continue;
                }

                $rowType = strtolower(trim((string) ($rowData['row_type'] ?? 'rule')));
                if (!in_array($rowType, ['rule', 'event'], true)) {
                    $fail("{$lineLabel}: row_type '{$rowData['row_type']}' không hợp lệ (chỉ nhận 'rule' hoặc 'event').");
                    continue;
                }

                $startDateStr = $rowData['start_date'] ?? $rowData['date'] ?? null;
                if (empty($startDateStr) || !strtotime((string) $startDateStr)) {
                    $fail("{$lineLabel}: ngày bắt đầu ('{$startDateStr}') không hợp lệ.");
                    continue;
                }

                $startDate = Carbon::parse((string) $startDateStr)->startOfDay();
                $endDateStr = $rowData['end_date'] ?? $rowData['start_date'] ?? $rowData['date'] ?? null;
                $endDate = empty($endDateStr) || !strtotime((string) $endDateStr)
                    ? $startDate->copy()
                    : Carbon::parse((string) $endDateStr)->startOfDay();

                if ($endDate->lt($startDate)) {
                    $fail("{$lineLabel}: ngày kết thúc ({$endDate->toDateString()}) nhỏ hơn ngày bắt đầu ({$startDate->toDateString()}).");
                    continue;
                }

                if ($startDate->lt($planStart) || $endDate->gt($planEnd)) {
                    $fail(sprintf(
                        "%s: ngày %s - %s nằm ngoài khoảng thời gian học kỳ (%s - %s).",
                        $lineLabel,
                        $startDate->toDateString(),
                        $endDate->toDateString(),
                        $planStart->toDateString(),
                        $planEnd->toDateString()
                    ));
                    continue;
                }

                $periodFrom = $this->parseOptionalPeriodValue($rowData['period_from'] ?? null);
                $periodTo = $this->parseOptionalPeriodValue($rowData['period_to'] ?? null);
                if ($periodFrom === null || $periodTo === null || $periodTo < $periodFrom) {
                    $fail("{$lineLabel}: tiết không hợp lệ (period_from='{$rowData['period_from']}', period_to='{$rowData['period_to']}').");
                    continue;
                }

                if ($rowType === 'event') {
                    $eventType = strtolower(trim((string) ($rowData['event_type'] ?? '')));
                    $title = trim((string) ($rowData['title'] ?? ''));

                    if (!in_array($eventType, ['review', 'exam', 'other'], true)) {
                        $fail("{$lineLabel}: event_type '{$eventType}' không hợp lệ (chỉ nhận review/exam/other).");
                        continue;
                    }

                    if ($title === '') {
                        $fail("{$lineLabel}: sự kiện phải nhập title.");
                        continue;
                    }

                    $note = trim((string) ($rowData['note'] ?? $rowData['content'] ?? '')) ?: null;
                    $recurrence = strtolower(trim((string) ($rowData['recurrence'] ?? '')));

                    if ($recurrence === '' || $recurrence === 'none') {
                        $parsed['events'][] = [
                            'class_id' => $class->id,
                            'class_key' => (string) $classKey,
                            'event_type' => $eventType,
                            'title' => $title,
                            'start_date' => $startDate->toDateString(),
                            'end_date' => $endDate->toDateString(),
                            'period_from' => $periodFrom,
                            'period_to' => $periodTo,
                            'color' => $this->semesterEventColor($eventType),
                            'note' => $note,
                            'sort_order' => $rowIndex,
                        ];

                        continue;
                    }

                    if ($recurrence !== 'monthly_weekday') {
                        $fail("{$lineLabel}: recurrence '{$recurrence}' không hợp lệ.");
                        continue;
                    }

                    $recurrenceWeekdayStr = (string) ($rowData['weekdays'] ?? $rowData['days_of_week'] ?? '');
                    $recurrenceWeekdays = $recurrenceWeekdayStr !== ''
                        ? $this->importFileReader()->parseWeekdaysFromString($recurrenceWeekdayStr)
                        : [];
                    if ($recurrenceWeekdays === []) {
                        $fail("{$lineLabel}: cần cột weekdays (một thứ) khi dùng recurrence monthly_weekday.");
                        continue;
                    }

                    $occurrence = $this->parseOccurrenceValue($rowData['occurrence'] ?? null);
                    if ($occurrence === null) {
                        $fail("{$lineLabel}: cột occurrence ('{$rowData['occurrence']}') không hợp lệ (1-4 hoặc last).");
                        continue;
                    }

                    $occurrenceDates = $this->expandMonthlyWeekdayDates($startDate, $endDate, $recurrenceWeekdays[0], $occurrence);
                    if ($occurrenceDates === []) {
                        $fail("{$lineLabel}: Không có ngày nào khớp với recurrence trong khoảng đã chọn.");
                        continue;
                    }

                    foreach ($occurrenceDates as $occurrenceDate) {
                        $parsed['events'][] = [
                            'class_id' => $class->id,
                            'class_key' => (string) $classKey,
                            'event_type' => $eventType,
                            'title' => $title,
                            'start_date' => $occurrenceDate->toDateString(),
                            'end_date' => $occurrenceDate->toDateString(),
                            'period_from' => $periodFrom,
                            'period_to' => $periodTo,
                            'color' => $this->semesterEventColor($eventType),
                            'note' => $note,
                            'sort_order' => $rowIndex,
                        ];
                    }

                    continue;
                }

                $weekdaysStr = $rowData['weekdays'] ?? $rowData['days_of_week'] ?? null;
                $daysOfWeek = !empty($weekdaysStr)
                    ? $this->importFileReader()->parseWeekdaysFromString((string) $weekdaysStr)
                    : [$startDate->dayOfWeekIso + 1];

                if ($daysOfWeek === []) {
                    $fail("{$lineLabel}: chưa ngày trong tuần hợp lệ.");
                    continue;
                }

                $subjectText = trim((string) ($rowData['subject'] ?? ''));
                if ($subjectText === '') {
                    $fail("{$lineLabel}: quy tắc phải nhập subject.");
                    continue;
                }

                try {
                    $subjectId = $this->resolveSubjectId($subjectText, $allowedSubjectIds);
                } catch (ValidationException $e) {
                    $fail("{$lineLabel}: " . $this->firstValidationMessage($e));
                    continue;
                }

                $parsed['templates'][] = [
                    'class_id' => $class->id,
                    'class_key' => (string) $classKey,
                    'subject_id' => $subjectId,
                    'subject_label' => $subjectText,
                    'source' => $lineLabel,
                    'day_of_week' => $daysOfWeek[0],
                    'days_of_week' => $daysOfWeek,
                    'session' => $periodTo <= 5 ? 'Sáng' : 'Chiều',
                    'period_range' => sprintf('%d-%d', $periodFrom, $periodTo),
                    'description' => trim((string) ($rowData['content'] ?? '')) ?: null,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ];
            }
        }

        return $parsed;
    }

    private function firstValidationMessage(ValidationException $e): string
    {
        foreach ($e->errors() as $messages) {
            if (is_array($messages) && $messages !== []) {
                return (string) reset($messages);
            }
        }

        return 'Không thể đọc file import.';
    }

    private function readImportRows(UploadedFile $file): array
    {
        return $this->importFileReader()->readRows($file);
    }

    private function importFileReader(): ScheduleImportFileReader
    {
        return new ScheduleImportFileReader();
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

    /**
     * Accepts 1-4 (1st..4th occurrence) or "last"/"cuoi"/"cuoi cung"/-1 for the
     * last occurrence of the weekday in the month. Returns null when invalid.
     */
    private function parseOccurrenceValue(mixed $value): ?int
    {
        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['last', 'cuoi', 'cuoi cung', '-1'], true)) {
            return -1;
        }

        if (is_numeric($normalized)) {
            $occurrence = (int) $normalized;
            return $occurrence >= 1 && $occurrence <= 4 ? $occurrence : null;
        }

        return null;
    }

    /**
     * The Nth (or last, when $occurrence is -1) occurrence of the given ISO
     * weekday (2 = Monday .. 8 = Sunday, matching normalizeWeekdays) within a
     * calendar month. Returns null when the month has no such occurrence
     * (e.g. a "5th Monday" that doesn't exist).
     */
    private function nthWeekdayOfMonth(int $year, int $month, int $isoWeekday, int $occurrence): ?Carbon
    {
        $carbonDayOfWeek = $isoWeekday === 8 ? 0 : $isoWeekday - 1;
        $base = Carbon::create($year, $month, 1)->startOfDay();

        $result = $occurrence === -1
            ? $base->copy()->lastOfMonth($carbonDayOfWeek)
            : $base->copy()->nthOfMonth($occurrence, $carbonDayOfWeek);

        return $result instanceof Carbon ? $result->startOfDay() : null;
    }

    /**
     * Expands a "same weekday, same occurrence-in-month" recurrence (e.g.
     * "2nd Monday of every month") into concrete dates covering every month
     * touched by [$start, $end], clamped to that range.
     *
     * @return array<int, Carbon>
     */
    private function expandMonthlyWeekdayDates(Carbon $start, Carbon $end, int $isoWeekday, int $occurrence): array
    {
        $dates = [];
        $cursor = $start->copy()->startOfMonth();
        $lastMonth = $end->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $occurrenceDate = $this->nthWeekdayOfMonth((int) $cursor->year, (int) $cursor->month, $isoWeekday, $occurrence);
            if ($occurrenceDate !== null && $occurrenceDate->gte($start) && $occurrenceDate->lte($end)) {
                $dates[] = $occurrenceDate;
            }
            $cursor->addMonth();
        }

        return $dates;
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
                'class_tab_rules' => "Môn học '{$subjectText}' không tồn tại trong hệ thống hoặc chưa được thêm vào danh mục môn học.",
            ]);
        }

        if ($allowedSubjectIds !== null && !in_array($subject->id, $allowedSubjectIds, true)) {
            throw ValidationException::withMessages([
                'class_tab_rules' => "Môn học '{$subjectText}' không thuộc chương trình đào tạo của khoa đào tạo đã chọn.",
            ]);
        }

        return $subject->id;
    }

    /**
     * @param array<int, string> $classCodes class_id => class code, for readable messages
     */
    private function validateTemplateEntries(array $entries, Carbon $planStart, Carbon $planEnd, array $classCodes, ScheduleValidationReport $report): void
    {
        $seenSlots = [];

        foreach ($entries as $entry) {
            $classKey = $entry['class_key'] ?? (string) $entry['class_id'];

            $report->attempt(
                function () use ($entry, $planStart, $planEnd, $classCodes, &$seenSlots): void {
                    $this->validateTemplateEntry($entry, $planStart, $planEnd, $classCodes, $seenSlots);
                },
                $classKey
            );
        }
    }

    /**
     * @param array<int, string> $classCodes
     * @param array<string, array{source: string, subject_label: string}> $seenSlots
     */
    private function validateTemplateEntry(array $entry, Carbon $planStart, Carbon $planEnd, array $classCodes, array &$seenSlots): void
    {
        $startDate = Carbon::parse($entry['start_date'])->startOfDay();
        $endDate = Carbon::parse($entry['end_date'])->endOfDay();
        $classCode = $classCodes[(int) $entry['class_id']] ?? (string) $entry['class_id'];
        $subjectLabel = $entry['subject_label'] ?? ('môn #' . $entry['subject_id']);
        $source = $entry['source'] ?? "quy tắc của lớp '{$classCode}'";

        if ($startDate->lt($planStart) || $endDate->gt($planEnd)) {
            throw ValidationException::withMessages([
                'class_tab_rules' => sprintf(
                    "%s (môn %s): có ngày %s - %s nằm ngoài khoảng thời gian học kỳ (%s - %s).",
                    $source,
                    $subjectLabel,
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                    $planStart->toDateString(),
                    $planEnd->toDateString()
                ),
            ]);
        }

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'class_tab_rules' => "{$source} (môn {$subjectLabel}): ngày kết thúc ({$endDate->toDateString()}) nhỏ hơn ngày bắt đầu ({$startDate->toDateString()}).",
            ]);
        }

        $daysOfWeek = $this->normalizeWeekdays($entry['days_of_week'] ?? []);
        if ($daysOfWeek === []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => "{$source} (môn {$subjectLabel}): phải có ít nhất một thứ học.",
            ]);
        }

        $periods = $this->parsePeriodRange((string) $entry['period_range']);
        if ($periods === []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => "{$source} (môn {$subjectLabel}): khoảng tiết '{$entry['period_range']}' không hợp lệ.",
            ]);
        }

        // Only the first clash between this entry and each earlier entry is
        // reported; further clashes between the same pair repeat the message.
        $clashes = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (!in_array($date->dayOfWeekIso + 1, $daysOfWeek, true)) {
                continue;
            }

            foreach ($periods as $period) {
                $slotKey = implode('|', [$entry['class_id'], $date->toDateString(), $period]);

                if (isset($seenSlots[$slotKey])) {
                    $previous = $seenSlots[$slotKey];
                    $pairKey = $previous['source'] . '|' . $previous['subject_label'];

                    if (!isset($clashes[$pairKey])) {
                        $clashes[$pairKey] = sprintf(
                            "Lớp '%s' bị trùng tiết %d ngày %s: %s (môn %s) trùng với %s (môn %s).",
                            $classCode,
                            $period,
                            $date->toDateString(),
                            $source,
                            $subjectLabel,
                            $previous['source'],
                            $previous['subject_label']
                        );
                    }

                    continue;
                }

                $seenSlots[$slotKey] = [
                    'source' => $source,
                    'subject_label' => $subjectLabel,
                ];
            }
        }

        if ($clashes !== []) {
            throw ValidationException::withMessages([
                'class_tab_rules' => array_values($clashes),
            ]);
        }
    }

    private function parsePeriodRange(string $range): array
    {
        if (preg_match('/^(\\d+)\\s*-\\s*(\\d+)$/', trim($range), $matches)) {
            return range((int) $matches[1], (int) $matches[2]);
        }

        return [];
    }

    private function parseGlobalSemesterEvents(mixed $rawEvents, Carbon $planStart, Carbon $planEnd, ScheduleValidationReport $report): array
    {
        if ($rawEvents === null || $rawEvents === '' || $rawEvents === []) {
            return [];
        }

        $decoded = is_string($rawEvents) ? json_decode($rawEvents, true) : $rawEvents;
        if (!is_array($decoded)) {
            $report->add('global_semester_events', 'Dữ liệu sự kiện nghỉ lễ không hợp lệ.');

            return [];
        }

        $rows = [];
        foreach ($decoded as $index => $event) {
            $row = $report->attempt(fn () => $this->buildGlobalSemesterEvent($event, $index, $planStart, $planEnd));
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null Null when the row is blank and should be ignored.
     */
    private function buildGlobalSemesterEvent(mixed $event, int|string $index, Carbon $planStart, Carbon $planEnd): ?array
    {
        if (!is_array($event)) {
            return null;
        }

        $title = trim((string) ($event['title'] ?? ''));
        $eventType = trim((string) ($event['event_type'] ?? ''));
        $startDate = $event['start_date'] ?? null;
        $endDate = $event['end_date'] ?? null;

        if ($title === '' || $eventType === '' || !$this->isDateValue($startDate) || !$this->isDateValue($endDate)) {
            return null;
        }

        if ($eventType !== 'holiday') {
            throw ValidationException::withMessages([
                'global_semester_events' => "Sự kiện '{$title}': sự kiện chung chỉ chấp nhận loại nghỉ lễ.",
            ]);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'global_semester_events' => "Sự kiện nghỉ lễ '{$title}' có ngày kết thúc nhỏ hơn ngày bắt đầu.",
            ]);
        }

        if ($start->lt($planStart) || $end->gt($planEnd)) {
            throw ValidationException::withMessages([
                'global_semester_events' => sprintf(
                    "Sự kiện nghỉ lễ '%s' có ngày %s - %s nằm ngoài khoảng thời gian học kỳ (%s - %s).",
                    $title,
                    $start->toDateString(),
                    $end->toDateString(),
                    $planStart->toDateString(),
                    $planEnd->toDateString()
                ),
            ]);
        }

        $periodFrom = $this->parseOptionalPeriodValue($event['period_from'] ?? null) ?? 1;
        $periodTo = $this->parseOptionalPeriodValue($event['period_to'] ?? null) ?? 9;

        if ($periodTo < $periodFrom) {
            throw ValidationException::withMessages([
                'global_semester_events' => "Sự kiện nghỉ lễ '{$title}' có tiết kết thúc nhỏ hơn tiết bắt đầu.",
            ]);
        }

        return [
            'class_id' => null,
            'class_key' => null,
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

    private function parseClassSemesterEvents(mixed $rawEvents, array $classMap, Carbon $planStart, Carbon $planEnd, ScheduleValidationReport $report): array
    {
        if ($rawEvents === null || $rawEvents === '' || $rawEvents === []) {
            return [];
        }

        $decoded = is_string($rawEvents) ? json_decode($rawEvents, true) : $rawEvents;
        if (!is_array($decoded)) {
            $report->add('class_semester_events', 'Dữ liệu sự kiện của lớp không hợp lệ.');

            return [];
        }

        $rows = [];

        foreach ($decoded as $classKey => $events) {
            if (!array_key_exists((string) $classKey, $classMap) || !is_array($events)) {
                continue;
            }

            $class = $classMap[(string) $classKey];

            foreach ($events as $index => $event) {
                $row = $report->attempt(
                    fn () => $this->buildClassSemesterEvent($event, $index, $class, (string) $classKey, $planStart, $planEnd),
                    $classKey
                );
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null Null when the row is blank and should be ignored.
     */
    private function buildClassSemesterEvent(mixed $event, int|string $index, TrainingClass $class, string $classKey, Carbon $planStart, Carbon $planEnd): ?array
    {
        if (!is_array($event)) {
            return null;
        }

        $title = trim((string) ($event['title'] ?? ''));
        $eventType = trim((string) ($event['event_type'] ?? ''));
        $startDate = $event['start_date'] ?? null;
        $endDate = $event['end_date'] ?? null;

        if ($title === '' || $eventType === '' || !$this->isDateValue($startDate) || !$this->isDateValue($endDate)) {
            return null;
        }

        if (!in_array($eventType, ['review', 'exam', 'other'], true)) {
            throw ValidationException::withMessages([
                'class_semester_events' => "Sự kiện '{$title}' của lớp '{$class->code}' chỉ chấp nhận loại ôn thi, thi hoặc khác.",
            ]);
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'class_semester_events' => "Sự kiện '{$title}' của lớp '{$class->code}' có ngày kết thúc nhỏ hơn ngày bắt đầu.",
            ]);
        }

        if ($start->lt($planStart) || $end->gt($planEnd)) {
            throw ValidationException::withMessages([
                'class_semester_events' => sprintf(
                    "Sự kiện '%s' của lớp '%s' có ngày %s - %s nằm ngoài khoảng thời gian học kỳ (%s - %s).",
                    $title,
                    $class->code,
                    $start->toDateString(),
                    $end->toDateString(),
                    $planStart->toDateString(),
                    $planEnd->toDateString()
                ),
            ]);
        }

        $periodFrom = $this->parseOptionalPeriodValue($event['period_from'] ?? null) ?? 1;
        $periodTo = $this->parseOptionalPeriodValue($event['period_to'] ?? null) ?? 9;

        if ($periodTo < $periodFrom) {
            throw ValidationException::withMessages([
                'class_semester_events' => "Sự kiện '{$title}' của lớp '{$class->code}' có tiết kết thúc nhỏ hơn tiết bắt đầu.",
            ]);
        }

        return [
            'class_id' => $class->id,
            'class_key' => $classKey,
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

    private function validateSemesterEventsWithinPlan(array $events, Carbon $planStart, Carbon $planEnd, ScheduleValidationReport $report): void
    {
        foreach ($events as $event) {
            $start = Carbon::parse($event['start_date'])->startOfDay();
            $end = Carbon::parse($event['end_date'])->endOfDay();
            $classKey = $event['class_key'] ?? null;
            $title = $event['title'] ?? ($event['event_type'] ?? 'không rõ');
            $classLabel = ($event['class_id'] ?? null) !== null ? $this->classLabelById((int) $event['class_id']) : 'toàn trường';

            if ($end->lt($start)) {
                $report->add('class_semester_events', "Sự kiện '{$title}' (lớp {$classLabel}) có ngày kết thúc nhỏ hơn ngày bắt đầu.", $classKey);

                continue;
            }

            if ($start->lt($planStart) || $end->gt($planEnd)) {
                $report->add('class_semester_events', sprintf(
                    "Sự kiện '%s' (lớp %s) có ngày %s - %s nằm ngoài khoảng thời gian học kỳ (%s - %s).",
                    $title,
                    $classLabel,
                    $start->toDateString(),
                    $end->toDateString(),
                    $planStart->toDateString(),
                    $planEnd->toDateString()
                ), $classKey);
            }
        }
    }

    private function validateSemesterEventConflicts(array $events, ScheduleValidationReport $report): void
    {
        $globalEvents = array_values(array_filter($events, fn (array $event) => ($event['class_id'] ?? null) === null));
        $classEvents = array_values(array_filter($events, fn (array $event) => ($event['class_id'] ?? null) !== null));

        $this->reportOverlapsWithinGroup($globalEvents, 'global_semester_events', 'Sự kiện nghỉ lễ', $report);

        $classGroups = collect($classEvents)->groupBy(fn (array $event) => (string) ($event['class_id'] ?? ''));
        foreach ($classGroups as $classId => $classGroup) {
            $this->reportOverlapsWithinGroup(
                $classGroup->values()->all(),
                'class_semester_events',
                'Sự kiện của lớp ' . $this->classLabelById((int) $classId),
                $report
            );
        }

        foreach ($globalEvents as $globalEvent) {
            foreach ($classEvents as $classEvent) {
                if ($this->dateRangesOverlap($globalEvent, $classEvent) && $this->eventPeriodsOverlap($globalEvent, $classEvent)) {
                    $classLabel = $this->classLabelById((int) $classEvent['class_id']);
                    $report->add(
                        'global_semester_events',
                        "Sự kiện nghỉ lễ '{$globalEvent['title']}' bị trùng ngày/tiết với sự kiện '{$classEvent['title']}' của lớp '{$classLabel}'.",
                        $classEvent['class_key'] ?? null
                    );
                }
            }
        }
    }

    private function reportOverlapsWithinGroup(array $events, string $errorKey, string $labelPrefix, ScheduleValidationReport $report): void
    {
        $count = count($events);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($this->dateRangesOverlap($events[$i], $events[$j]) && $this->eventPeriodsOverlap($events[$i], $events[$j])) {
                    $report->add(
                        $errorKey,
                        $labelPrefix . " '" . ($events[$i]['title'] ?? 'sự kiện') . "' bị trùng ngày/tiết với '" . ($events[$j]['title'] ?? 'sự kiện') . "'.",
                        $events[$i]['class_key'] ?? null
                    );
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
     * One message per class listing all of its uncovered months.
     *
     * @param array<int, array{class_id:int, month:int, year:int}> $gaps
     */
    private function reportCoverageGaps(array $gaps, ScheduleValidationReport $report): void
    {
        $monthsByClass = [];
        foreach ($gaps as $gap) {
            $monthsByClass[(int) $gap['class_id']][] = sprintf('%d/%d', $gap['month'], $gap['year']);
        }

        foreach ($monthsByClass as $classId => $months) {
            $report->add(
                'class_tab_rules',
                sprintf('Lớp %s thiếu dữ liệu lịch (quy tắc/sự kiện) cho tháng %s.', $this->classLabelById($classId), implode(', ', array_unique($months))),
                $classId
            );
        }
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
