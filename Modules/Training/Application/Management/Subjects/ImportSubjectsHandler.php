<?php

namespace Modules\Training\Application\Management\Subjects;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;

class ImportSubjectsHandler
{
    private const MAX_ROWS = 1000;

    private const ALLOWED_STATUSES = ['active', 'inactive'];

    public function handle(ImportSubjectsRequest $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        [$rows, $errors] = $this->readRows($file);

        $errors = array_merge($errors, $this->findExistingErrors($rows));

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'import_file' => $errors,
            ]);
        }

        $now = now();
        $records = array_map(fn (array $row): array => [
            'code' => $row['code'],
            'name' => $row['name'],
            'department_id' => $row['department_id'],
            'total_periods' => $row['total_periods'],
            'status' => $row['status'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        DB::transaction(function () use ($records): void {
            foreach (array_chunk($records, 500) as $chunk) {
                Subject::query()->insert($chunk);
            }
        });

        return response()->json([
            'message' => 'Nhập danh sách môn học thành công.',
            'imported_count' => count($records),
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

            $departmentsByCode = Department::query()
                ->get(['id', 'code'])
                ->keyBy(fn (Department $department): string => mb_strtolower($department->code, 'UTF-8'));

            $rows = [];
            $errors = [];
            $seenCodes = [];
            $seenNames = [];
            $lineNumber = 1;
            $dataRowCount = 0;

            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $lineNumber++;

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                $dataRowCount++;
                if ($dataRowCount > self::MAX_ROWS) {
                    $errors[] = 'File CSV chỉ được chứa tối đa 1.000 dòng dữ liệu.';
                    break;
                }

                $row = $this->normalizeRow($values, $headerMap);
                $rowErrors = $this->validateRow($row, $lineNumber, $seenCodes, $seenNames, $departmentsByCode);

                if ($rowErrors !== []) {
                    array_push($errors, ...$rowErrors);
                    continue;
                }

                $normalizedCode = mb_strtolower($row['code'], 'UTF-8');
                $normalizedName = mb_strtolower($row['name'], 'UTF-8');
                $seenCodes[$normalizedCode] = $lineNumber;
                $seenNames[$normalizedName] = $lineNumber;

                $department = $row['department_code'] === ''
                    ? null
                    : $departmentsByCode->get(mb_strtolower($row['department_code'], 'UTF-8'));

                $rows[] = [
                    'line_number' => $lineNumber,
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'department_id' => $department?->id,
                    'total_periods' => $row['total_periods'] === '' ? null : (int) $row['total_periods'],
                    'status' => $row['status'] === '' ? 'active' : mb_strtolower($row['status'], 'UTF-8'),
                ];
            }

            if ($dataRowCount === 0) {
                $errors[] = 'File CSV không có dòng môn học nào.';
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

        foreach (['code', 'name', 'department_code'] as $requiredHeader) {
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

        foreach (['code', 'name', 'department_code', 'total_periods', 'status'] as $column) {
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
     * @param array<string, int> $seenNames
     * @param \Illuminate\Support\Collection<string, Department> $departmentsByCode
     * @return array<int, string>
     */
    private function validateRow(array $row, int $lineNumber, array $seenCodes, array $seenNames, $departmentsByCode): array
    {
        $errors = [];

        foreach ($row as $value) {
            if (!mb_check_encoding($value, 'UTF-8')) {
                return ["Dòng {$lineNumber}: dữ liệu phải sử dụng mã hóa UTF-8."];
            }
        }

        if ($row['code'] === '') {
            $errors[] = "Dòng {$lineNumber}: code là bắt buộc.";
        } elseif (mb_strlen($row['code']) > 255) {
            $errors[] = "Dòng {$lineNumber}: code không được dài quá 255 ký tự.";
        } else {
            $normalizedCode = mb_strtolower($row['code'], 'UTF-8');
            if (array_key_exists($normalizedCode, $seenCodes)) {
                $errors[] = "Dòng {$lineNumber}: mã môn học '{$row['code']}' bị trùng với dòng {$seenCodes[$normalizedCode]}.";
            }
        }

        if ($row['name'] === '') {
            $errors[] = "Dòng {$lineNumber}: name là bắt buộc.";
        } elseif (mb_strlen($row['name']) > 255) {
            $errors[] = "Dòng {$lineNumber}: name không được dài quá 255 ký tự.";
        } else {
            $normalizedName = mb_strtolower($row['name'], 'UTF-8');
            if (array_key_exists($normalizedName, $seenNames)) {
                $errors[] = "Dòng {$lineNumber}: tên môn học '{$row['name']}' bị trùng với dòng {$seenNames[$normalizedName]}.";
            }
        }

        if ($row['department_code'] === '') {
            $errors[] = "Dòng {$lineNumber}: department_code là bắt buộc.";
        } elseif (!$departmentsByCode->has(mb_strtolower($row['department_code'], 'UTF-8'))) {
            $errors[] = "Dòng {$lineNumber}: department_code '{$row['department_code']}' không tồn tại.";
        }

        if ($row['total_periods'] !== '' && (!ctype_digit($row['total_periods']) || (int) $row['total_periods'] < 1)) {
            $errors[] = "Dòng {$lineNumber}: total_periods phải là số nguyên lớn hơn hoặc bằng 1.";
        }

        $status = $row['status'] === '' ? 'active' : mb_strtolower($row['status'], 'UTF-8');
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            $errors[] = "Dòng {$lineNumber}: status phải là active hoặc inactive.";
        }

        return $errors;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, string>
     */
    private function findExistingErrors(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $rowsByCode = [];
        $rowsByName = [];
        foreach ($rows as $row) {
            $rowsByCode[mb_strtolower($row['code'], 'UTF-8')] = $row;
            $rowsByName[mb_strtolower($row['name'], 'UTF-8')] = $row;
        }

        $existingCodes = [];
        foreach (array_chunk(array_keys($rowsByCode), 500) as $codeChunk) {
            Subject::query()
                ->whereIn(DB::raw('LOWER(code)'), $codeChunk)
                ->pluck('code')
                ->each(function (string $code) use (&$existingCodes): void {
                    $existingCodes[mb_strtolower($code, 'UTF-8')] = true;
                });
        }

        $existingNames = [];
        foreach (array_chunk(array_keys($rowsByName), 500) as $nameChunk) {
            Subject::query()
                ->whereIn(DB::raw('LOWER(name)'), $nameChunk)
                ->pluck('name')
                ->each(function (string $name) use (&$existingNames): void {
                    $existingNames[mb_strtolower($name, 'UTF-8')] = true;
                });
        }

        $errors = [];
        foreach ($existingCodes as $code => $_) {
            $row = $rowsByCode[$code];
            $errors[] = "Dòng {$row['line_number']}: mã môn học '{$row['code']}' đã tồn tại.";
        }

        foreach ($existingNames as $name => $_) {
            $row = $rowsByName[$name];
            $message = "Dòng {$row['line_number']}: tên môn học '{$row['name']}' đã tồn tại.";
            if (!in_array($message, $errors, true)) {
                $errors[] = $message;
            }
        }

        return $errors;
    }
}
