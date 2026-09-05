<?php

namespace Modules\Schedule\Application\CreateScheduleSemester;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\Shared\ScheduleImportFileReader;

/**
 * Parses an uploaded schedule import file (.xlsx/.csv/.txt) for the
 * dry-run preview. Mirrors the row rules CreateScheduleSemesterHandler
 * enforces on real import, but never throws for row-level problems:
 * invalid rows are skipped and reported back as warnings so the preview
 * can still render whatever is valid instead of failing outright.
 */
class PreviewImportFileParser
{
    private const MAX_WARNINGS = 30;

    public function __construct(
        private readonly ScheduleImportFileReader $reader = new ScheduleImportFileReader()
    ) {}

    /**
     * @return array{rules: array, events: array, warnings: array<int, string>}
     */
    public function parse(UploadedFile $file, ?string $classCode, Carbon $planStart, Carbon $planEnd): array
    {
        $result = ['rules' => [], 'events' => [], 'warnings' => []];

        try {
            $sheetRows = $this->reader->readRows($file);
        } catch (ValidationException $e) {
            $result['warnings'][] = $this->firstMessage($e);

            return $result;
        }

        if ($sheetRows === []) {
            $result['warnings'][] = 'File import khong co du lieu.';

            return $result;
        }

        $headers = array_shift($sheetRows);
        if (!is_array($headers) || $headers === []) {
            $result['warnings'][] = 'File import khong co dong tieu de hop le.';

            return $result;
        }

        $headerMap = array_flip(array_map(
            fn ($header) => $this->reader->normalizeHeader($header),
            $headers
        ));

        foreach (['class_code', 'period_from', 'period_to'] as $column) {
            if (!array_key_exists($column, $headerMap)) {
                $result['warnings'][] = "File import thieu cot bat buoc '{$column}'.";

                return $result;
            }
        }

        if (!array_key_exists('date', $headerMap) && !array_key_exists('start_date', $headerMap)) {
            $result['warnings'][] = "File import phai co cot 'date' hoac 'start_date'.";

            return $result;
        }

        foreach ($sheetRows as $rowIndex => $row) {
            if (count($result['warnings']) >= self::MAX_WARNINGS) {
                $result['warnings'][] = 'Con nhieu dong loi khac, chi hien thi ' . self::MAX_WARNINGS . ' canh bao dau tien.';
                break;
            }

            if (!is_array($row) || count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $lineLabel = 'Dong #' . ($rowIndex + 2);

            $rowData = [];
            foreach ($headerMap as $column => $index) {
                $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $rowClassCode = (string) ($rowData['class_code'] ?? '');
            if ($classCode !== null && Str::upper($rowClassCode) !== Str::upper($classCode)) {
                $result['warnings'][] = "{$lineLabel}: ma lop '{$rowClassCode}' khong khop voi lop dang xem truoc ('{$classCode}').";
                continue;
            }

            $rowType = strtolower(trim((string) ($rowData['row_type'] ?? 'rule')));
            if (!in_array($rowType, ['rule', 'event'], true)) {
                $result['warnings'][] = "{$lineLabel}: row_type khong hop le.";
                continue;
            }

            $startDateStr = $rowData['start_date'] ?? $rowData['date'] ?? null;
            if (empty($startDateStr) || !strtotime((string) $startDateStr)) {
                $result['warnings'][] = "{$lineLabel}: chua ngay bat dau hop le.";
                continue;
            }

            $startDate = Carbon::parse((string) $startDateStr)->startOfDay();
            $endDateStr = $rowData['end_date'] ?? $rowData['start_date'] ?? $rowData['date'] ?? null;
            $endDate = empty($endDateStr) || !strtotime((string) $endDateStr)
                ? $startDate->copy()
                : Carbon::parse((string) $endDateStr)->startOfDay();

            if ($endDate->lt($startDate)) {
                $result['warnings'][] = "{$lineLabel}: ngay ket thuc nho hon ngay bat dau.";
                continue;
            }

            if ($startDate->lt($planStart) || $endDate->gt($planEnd)) {
                $result['warnings'][] = sprintf(
                    "%s: ngay %s - %s nam ngoai khoang thoi gian hoc ky (%s - %s).",
                    $lineLabel,
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                    $planStart->toDateString(),
                    $planEnd->toDateString()
                );
                continue;
            }

            $periodFrom = $this->parsePeriod($rowData['period_from'] ?? null);
            $periodTo = $this->parsePeriod($rowData['period_to'] ?? null);
            if ($periodFrom === null || $periodTo === null || $periodTo < $periodFrom) {
                $result['warnings'][] = "{$lineLabel}: tiet khong hop le.";
                continue;
            }

            if ($rowType === 'event') {
                $eventType = strtolower(trim((string) ($rowData['event_type'] ?? '')));
                $title = trim((string) ($rowData['title'] ?? ''));

                if (!in_array($eventType, ['review', 'exam', 'other'], true)) {
                    $result['warnings'][] = "{$lineLabel}: event_type khong hop le.";
                    continue;
                }

                if ($title === '') {
                    $result['warnings'][] = "{$lineLabel}: su kien phai nhap title.";
                    continue;
                }

                $recurrence = strtolower(trim((string) ($rowData['recurrence'] ?? '')));

                if ($recurrence === '' || $recurrence === 'none') {
                    $result['events'][] = [
                        'title' => $title,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'period_from' => $periodFrom,
                        'period_to' => $periodTo,
                    ];

                    continue;
                }

                if ($recurrence !== 'monthly_weekday') {
                    $result['warnings'][] = "{$lineLabel}: recurrence khong hop le.";
                    continue;
                }

                $recurrenceWeekdayStr = (string) ($rowData['weekdays'] ?? $rowData['days_of_week'] ?? '');
                $recurrenceWeekdays = $recurrenceWeekdayStr !== ''
                    ? $this->reader->parseWeekdaysFromString($recurrenceWeekdayStr)
                    : [];

                if ($recurrenceWeekdays === []) {
                    $result['warnings'][] = "{$lineLabel}: can cot weekdays (mot thu) khi dung recurrence monthly_weekday.";
                    continue;
                }

                $occurrence = $this->parseOccurrenceValue($rowData['occurrence'] ?? null);
                if ($occurrence === null) {
                    $result['warnings'][] = "{$lineLabel}: cot occurrence khong hop le (1-4 hoac last).";
                    continue;
                }

                $occurrenceDates = $this->expandMonthlyWeekdayDates($startDate, $endDate, $recurrenceWeekdays[0], $occurrence);
                if ($occurrenceDates === []) {
                    $result['warnings'][] = "{$lineLabel}: khong co ngay nao khop voi recurrence trong khoang da chon.";
                    continue;
                }

                foreach ($occurrenceDates as $occurrenceDate) {
                    $result['events'][] = [
                        'title' => $title,
                        'start_date' => $occurrenceDate->toDateString(),
                        'end_date' => $occurrenceDate->toDateString(),
                        'period_from' => $periodFrom,
                        'period_to' => $periodTo,
                    ];
                }

                continue;
            }

            $weekdaysStr = $rowData['weekdays'] ?? $rowData['days_of_week'] ?? null;
            $daysOfWeek = !empty($weekdaysStr)
                ? $this->reader->parseWeekdaysFromString((string) $weekdaysStr)
                : [$startDate->dayOfWeekIso + 1];

            if ($daysOfWeek === []) {
                $result['warnings'][] = "{$lineLabel}: chua ngay trong tuan hop le.";
                continue;
            }

            $subjectText = trim((string) ($rowData['subject'] ?? ''));
            if ($subjectText === '') {
                $result['warnings'][] = "{$lineLabel}: quy tac phai nhap subject.";
                continue;
            }

            $result['rules'][] = [
                'subject' => $subjectText,
                'weekdays' => $daysOfWeek,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ];
        }

        return $result;
    }

    private function parsePeriod(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $period = (int) $value;

        return $period >= 1 && $period <= 9 ? $period : null;
    }

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

    private function firstMessage(ValidationException $e): string
    {
        foreach ($e->errors() as $messages) {
            if (is_array($messages) && $messages !== []) {
                return (string) reset($messages);
            }
        }

        return 'Khong the doc file import.';
    }
}
