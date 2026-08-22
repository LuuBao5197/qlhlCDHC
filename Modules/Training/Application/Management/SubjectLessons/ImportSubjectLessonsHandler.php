<?php

namespace Modules\Training\Application\Management\SubjectLessons;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\TrainingProgram;

class ImportSubjectLessonsHandler
{
    private const MAX_ROWS = 1000;

    public function handle(ImportSubjectLessonsRequest $request): JsonResponse
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
            'subject_id' => $row['subject_id'],
            'training_program_id' => $row['training_program_id'],
            'lesson_no' => $row['lesson_no'],
            'title' => $row['title'],
            'expected_periods' => $row['expected_periods'],
            'note' => $row['note'] === '' ? null : $row['note'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        DB::transaction(function () use ($records): void {
            foreach (array_chunk($records, 500) as $chunk) {
                SubjectLesson::query()->insert($chunk);
            }
        });

        return response()->json([
            'message' => 'Nhập danh sách bài học thành công.',
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

            $subjectsByCode = Subject::query()
                ->get(['id', 'code'])
                ->keyBy(fn (Subject $subject): string => mb_strtolower($subject->code, 'UTF-8'));

            $trainingProgramsByCode = TrainingProgram::query()
                ->get(['id', 'code'])
                ->keyBy(fn (TrainingProgram $trainingProgram): string => mb_strtolower($trainingProgram->code, 'UTF-8'));

            $linkedSubjectProgramPairs = [];
            DB::table('subject_training_program')
                ->select('subject_id', 'training_program_id')
                ->get()
                ->each(function ($pivot) use (&$linkedSubjectProgramPairs): void {
                    $linkedSubjectProgramPairs[$pivot->subject_id . ':' . $pivot->training_program_id] = true;
                });

            $rows = [];
            $errors = [];
            $seenPairs = [];
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
                $rowErrors = $this->validateRow($row, $lineNumber, $subjectsByCode, $trainingProgramsByCode, $linkedSubjectProgramPairs);

                if ($rowErrors !== []) {
                    array_push($errors, ...$rowErrors);
                    continue;
                }

                $subject = $subjectsByCode->get(mb_strtolower($row['subject_code'], 'UTF-8'));
                $trainingProgram = $trainingProgramsByCode->get(mb_strtolower($row['training_program_code'], 'UTF-8'));
                $pairKey = $subject->id . ':' . $trainingProgram->id . ':' . $row['code'];

                if (array_key_exists($pairKey, $seenPairs)) {
                    $errors[] = "Dòng {$lineNumber}: bài học '{$row['code']}' của môn '{$row['subject_code']}' - chương trình '{$row['training_program_code']}' bị trùng với dòng {$seenPairs[$pairKey]}.";
                    continue;
                }
                $seenPairs[$pairKey] = $lineNumber;

                $rows[] = [
                    'line_number' => $lineNumber,
                    'subject_code' => $row['subject_code'],
                    'subject_id' => $subject->id,
                    'training_program_code' => $row['training_program_code'],
                    'training_program_id' => $trainingProgram->id,
                    'lesson_no' => (int) $row['code'],
                    'title' => $row['name'],
                    'expected_periods' => $row['expected_periods'] === '' ? null : (int) $row['expected_periods'],
                    'note' => $row['note'],
                ];
            }

            if ($dataRowCount === 0) {
                $errors[] = 'File CSV không có dòng bài học nào.';
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

        foreach (['subject_code', 'training_program_code', 'code', 'name'] as $requiredHeader) {
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

        foreach (['subject_code', 'training_program_code', 'code', 'name', 'expected_periods', 'note'] as $column) {
            $value = array_key_exists($column, $headerMap)
                ? (string) ($values[$headerMap[$column]] ?? '')
                : '';
            $value = trim($value);
            $row[$column] = $column === 'note'
                ? $value
                : (preg_replace('/\s+/u', ' ', $value) ?? $value);
        }

        return $row;
    }

    /**
     * @param array<string, string> $row
     * @param \Illuminate\Support\Collection<string, Subject> $subjectsByCode
     * @param \Illuminate\Support\Collection<string, TrainingProgram> $trainingProgramsByCode
     * @param array<string, bool> $linkedSubjectProgramPairs
     * @return array<int, string>
     */
    private function validateRow(array $row, int $lineNumber, $subjectsByCode, $trainingProgramsByCode, array $linkedSubjectProgramPairs): array
    {
        $errors = [];

        foreach ($row as $value) {
            if (!mb_check_encoding($value, 'UTF-8')) {
                return ["Dòng {$lineNumber}: dữ liệu phải sử dụng mã hóa UTF-8."];
            }
        }

        $subject = null;
        if ($row['subject_code'] === '') {
            $errors[] = "Dòng {$lineNumber}: subject_code là bắt buộc.";
        } elseif (!$subjectsByCode->has(mb_strtolower($row['subject_code'], 'UTF-8'))) {
            $errors[] = "Dòng {$lineNumber}: subject_code '{$row['subject_code']}' không tồn tại.";
        } else {
            $subject = $subjectsByCode->get(mb_strtolower($row['subject_code'], 'UTF-8'));
        }

        $trainingProgram = null;
        if ($row['training_program_code'] === '') {
            $errors[] = "Dòng {$lineNumber}: training_program_code là bắt buộc.";
        } elseif (!$trainingProgramsByCode->has(mb_strtolower($row['training_program_code'], 'UTF-8'))) {
            $errors[] = "Dòng {$lineNumber}: training_program_code '{$row['training_program_code']}' không tồn tại.";
        } else {
            $trainingProgram = $trainingProgramsByCode->get(mb_strtolower($row['training_program_code'], 'UTF-8'));
        }

        if ($subject !== null && $trainingProgram !== null
            && !isset($linkedSubjectProgramPairs[$subject->id . ':' . $trainingProgram->id])
        ) {
            $errors[] = "Dòng {$lineNumber}: môn '{$row['subject_code']}' chưa được gán vào chương trình đào tạo '{$row['training_program_code']}'.";
        }

        if ($row['code'] === '') {
            $errors[] = "Dòng {$lineNumber}: code là bắt buộc.";
        } elseif (!ctype_digit($row['code']) || (int) $row['code'] < 1) {
            $errors[] = "Dòng {$lineNumber}: code phải là số nguyên lớn hơn hoặc bằng 1.";
        }

        if ($row['name'] === '') {
            $errors[] = "Dòng {$lineNumber}: name là bắt buộc.";
        } elseif (mb_strlen($row['name']) > 255) {
            $errors[] = "Dòng {$lineNumber}: name không được dài quá 255 ký tự.";
        }

        if ($row['expected_periods'] !== '' && (!ctype_digit($row['expected_periods']) || (int) $row['expected_periods'] < 1)) {
            $errors[] = "Dòng {$lineNumber}: expected_periods phải là số nguyên lớn hơn hoặc bằng 1.";
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

        $subjectIds = array_values(array_unique(array_map(fn (array $row): int => $row['subject_id'], $rows)));

        $existingPairs = [];
        foreach (array_chunk($subjectIds, 500) as $idChunk) {
            SubjectLesson::query()
                ->whereIn('subject_id', $idChunk)
                ->get(['subject_id', 'training_program_id', 'lesson_no'])
                ->each(function (SubjectLesson $lesson) use (&$existingPairs): void {
                    $existingPairs[$lesson->subject_id . ':' . $lesson->training_program_id . ':' . $lesson->lesson_no] = true;
                });
        }

        $errors = [];
        foreach ($rows as $row) {
            $pairKey = $row['subject_id'] . ':' . $row['training_program_id'] . ':' . $row['lesson_no'];
            if (array_key_exists($pairKey, $existingPairs)) {
                $errors[] = "Dòng {$row['line_number']}: bài học '{$row['lesson_no']}' của môn '{$row['subject_code']}' - chương trình '{$row['training_program_code']}' đã tồn tại.";
            }
        }

        return $errors;
    }
}
