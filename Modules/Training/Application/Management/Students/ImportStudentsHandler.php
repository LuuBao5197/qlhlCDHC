<?php

namespace Modules\Training\Application\Management\Students;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Training\Models\Student;
use Modules\Training\Models\TrainingClass;

class ImportStudentsHandler
{
    private const MAX_ROWS = 5000;

    private const ALLOWED_STATUSES = ['active', 'suspended', 'graduated'];

    public function handle(ImportStudentsRequest $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        $class = TrainingClass::query()->findOrFail((int) $request->validated('class_id'));
        [$rows, $errors] = $this->readRows($file);

        $errors = array_merge($errors, $this->findExistingCodeErrors($rows));

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'import_file' => $errors,
            ]);
        }

        $now = now();
        $records = array_map(fn (array $row): array => [
            'class_id' => $class->id,
            'student_code' => $row['student_code'],
            'name' => $row['name'],
            'date_of_birth' => $row['date_of_birth'],
            'status' => $row['status'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        DB::transaction(function () use ($records): void {
            foreach (array_chunk($records, 500) as $chunk) {
                Student::query()->insert($chunk);
            }
        });

        return response()->json([
            'message' => 'Nhập danh sách học viên thành công.',
            'imported_count' => count($records),
            'class' => [
                'id' => $class->id,
                'code' => $class->code,
                'name' => $class->name,
            ],
        ]);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function readRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return [[], ['Không thể đọc file CSV đã tải lên.']];
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [[], ['File CSV không có dữ liệu.']];
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $headers = fgetcsv($handle, 0, $delimiter, '"', '');
            if ($headers === false) {
                return [[], ['Không thể đọc hàng tiêu đề của file CSV.']];
            }

            $headerMap = $this->buildHeaderMap($headers);
            $headerErrors = $this->validateHeaders($headerMap, $headers);
            if ($headerErrors !== []) {
                return [[], $headerErrors];
            }

            $rows = [];
            $errors = [];
            $seenCodes = [];
            $lineNumber = 1;
            $dataRowCount = 0;

            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $lineNumber++;

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                $dataRowCount++;
                if ($dataRowCount > self::MAX_ROWS) {
                    $errors[] = 'File CSV chỉ được chứa tối đa 5.000 dòng dữ liệu.';
                    break;
                }

                $row = $this->normalizeRow($values, $headerMap);
                $rowErrors = $this->validateRow($row, $lineNumber, $seenCodes);

                if ($rowErrors !== []) {
                    array_push($errors, ...$rowErrors);
                    continue;
                }

                $normalizedCode = mb_strtolower($row['student_code'], 'UTF-8');
                $seenCodes[$normalizedCode] = $lineNumber;
                $rows[] = [
                    'line_number' => $lineNumber,
                    'student_code' => $row['student_code'],
                    'name' => $row['name'],
                    'date_of_birth' => $this->parseDate($row['date_of_birth']),
                    'status' => $row['status'] === '' ? 'active' : mb_strtolower($row['status'], 'UTF-8'),
                ];
            }

            if ($dataRowCount === 0) {
                $errors[] = 'File CSV không có dòng học viên nào.';
            }

            return [$rows, $errors];
        } finally {
            fclose($handle);
        }
    }

    private function detectDelimiter(string $line): string
    {
        $commaColumns = count(str_getcsv($line, ',', '"', ''));
        $semicolonColumns = count(str_getcsv($line, ';', '"', ''));

        return $semicolonColumns > $commaColumns ? ';' : ',';
    }

    /**
     * @param array<int, string|null> $headers
     * @return array<string, int>
     */
    private function buildHeaderMap(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $header) {
            $normalized = mb_strtolower(trim((string) $header), 'UTF-8');
            if ($index === 0) {
                $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $normalized) ?? $normalized;
            }

            if ($normalized !== '' && !array_key_exists($normalized, $map)) {
                $map[$normalized] = $index;
            }
        }

        return $map;
    }

    /**
     * @param array<string, int> $headerMap
     * @param array<int, string|null> $headers
     * @return array<int, string>
     */
    private function validateHeaders(array $headerMap, array $headers): array
    {
        $errors = [];

        foreach (['student_code', 'name'] as $requiredHeader) {
            if (!array_key_exists($requiredHeader, $headerMap)) {
                $errors[] = "File CSV thiếu cột bắt buộc '{$requiredHeader}'.";
            }
        }

        $normalizedHeaders = array_map(function ($header): string {
            $normalized = mb_strtolower(trim((string) $header), 'UTF-8');

            return preg_replace('/^\xEF\xBB\xBF/', '', $normalized) ?? $normalized;
        }, $headers);

        $duplicates = array_keys(array_filter(array_count_values($normalizedHeaders), fn (int $count, string $header): bool => $header !== '' && $count > 1, ARRAY_FILTER_USE_BOTH));
        foreach ($duplicates as $duplicate) {
            $errors[] = "File CSV có cột '{$duplicate}' bị lặp.";
        }

        return $errors;
    }

    /**
     * @param array<int, string|null> $values
     */
    private function isEmptyRow(array $values): bool
    {
        return count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0;
    }

    /**
     * @param array<int, string|null> $values
     * @param array<string, int> $headerMap
     * @return array<string, string>
     */
    private function normalizeRow(array $values, array $headerMap): array
    {
        $row = [];

        foreach (['student_code', 'name', 'date_of_birth', 'status'] as $column) {
            $value = array_key_exists($column, $headerMap)
                ? (string) ($values[$headerMap[$column]] ?? '')
                : '';
            $value = trim($value);
            $row[$column] = preg_replace('/\s+/u', ' ', $value) ?? $value;
        }

        return $row;
    }

    /**
     * @param array<string, string> $row
     * @param array<string, int> $seenCodes
     * @return array<int, string>
     */
    private function validateRow(array $row, int $lineNumber, array $seenCodes): array
    {
        $errors = [];

        foreach ($row as $value) {
            if (!mb_check_encoding($value, 'UTF-8')) {
                return ["Dòng {$lineNumber}: dữ liệu phải sử dụng mã hóa UTF-8."];
            }
        }

        if ($row['student_code'] === '') {
            $errors[] = "Dòng {$lineNumber}: student_code là bắt buộc.";
        } elseif (mb_strlen($row['student_code']) > 255) {
            $errors[] = "Dòng {$lineNumber}: student_code không được dài quá 255 ký tự.";
        } else {
            $normalizedCode = mb_strtolower($row['student_code'], 'UTF-8');
            if (array_key_exists($normalizedCode, $seenCodes)) {
                $errors[] = "Dòng {$lineNumber}: mã học viên '{$row['student_code']}' bị trùng với dòng {$seenCodes[$normalizedCode]}.";
            }
        }

        if ($row['name'] === '') {
            $errors[] = "Dòng {$lineNumber}: name là bắt buộc.";
        } elseif (mb_strlen($row['name']) > 255) {
            $errors[] = "Dòng {$lineNumber}: name không được dài quá 255 ký tự.";
        }

        if ($row['date_of_birth'] !== '' && $this->parseDate($row['date_of_birth']) === null) {
            $errors[] = "Dòng {$lineNumber}: date_of_birth phải có dạng dd/mm/yyyy hoặc yyyy-mm-dd.";
        }

        $status = $row['status'] === '' ? 'active' : mb_strtolower($row['status'], 'UTF-8');
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            $errors[] = "Dòng {$lineNumber}: status phải là active, suspended hoặc graduated.";
        }

        return $errors;
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $dateErrors = DateTimeImmutable::getLastErrors();
            $isValid = $date !== false
                && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
                && $date->format($format) === $value;

            if ($isValid) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, string>
     */
    private function findExistingCodeErrors(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $rowsByCode = [];
        foreach ($rows as $row) {
            $rowsByCode[mb_strtolower($row['student_code'], 'UTF-8')] = $row;
        }

        $existingCodes = [];
        foreach (array_chunk(array_keys($rowsByCode), 500) as $codeChunk) {
            Student::query()
                ->whereIn(DB::raw('LOWER(student_code)'), $codeChunk)
                ->pluck('student_code')
                ->each(function (string $code) use (&$existingCodes): void {
                    $existingCodes[mb_strtolower($code, 'UTF-8')] = true;
                });
        }

        $errors = [];
        foreach ($existingCodes as $code => $_) {
            $row = $rowsByCode[$code];
            $errors[] = "Dòng {$row['line_number']}: mã học viên '{$row['student_code']}' đã tồn tại.";
        }

        return $errors;
    }
}
