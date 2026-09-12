<?php

namespace Modules\Training\Application\Management\Teachers;

use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Training\Models\Department;
use Modules\Training\Models\Teacher;

class ImportTeachersHandler
{
    private const MAX_ROWS = 500;

    private const ALLOWED_STATUSES = ['active', 'inactive'];

    public function handle(ImportTeachersRequest $request): JsonResponse
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

        $actor = $request->user();
        $teachers = DB::transaction(function () use ($rows): array {
            $created = [];
            foreach ($rows as $row) {
                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make((string) config('accounts.default_password')),
                    'must_change_password' => true,
                    'role' => User::ROLE_TEACHER,
                    'status' => User::STATUS_APPROVED,
                    'email_verified_at' => now(),
                    'employee_code' => $row['teacher_code'],
                    'department_id' => $row['department_id'],
                ]);

                $created[] = Teacher::create([
                    'teacher_code' => $row['teacher_code'],
                    'name' => $row['name'],
                    'status' => $row['status'],
                    'department_id' => $row['department_id'],
                    'user_id' => $user->id,
                ]);
            }

            return $created;
        });

        foreach ($teachers as $teacher) {
            if ($actor !== null) {
                app(InternalNotificationService::class)->notifyAccountCreated($teacher->user, $actor);
            }
        }

        return response()->json([
            'message' => 'Nhập danh sách giáo viên thành công. Mật khẩu mặc định của các tài khoản mới: '
                . config('accounts.default_password') . '.',
            'imported_count' => count($teachers),
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
            $seenEmails = [];
            $lineNumber = 1;
            $dataRowCount = 0;

            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $lineNumber++;

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                $dataRowCount++;
                if ($dataRowCount > self::MAX_ROWS) {
                    $errors[] = 'File CSV chỉ được chứa tối đa 500 dòng dữ liệu.';
                    break;
                }

                $row = $this->normalizeRow($values, $headerMap);
                $rowErrors = $this->validateRow($row, $lineNumber, $seenCodes, $seenEmails, $departmentsByCode);

                if ($rowErrors !== []) {
                    array_push($errors, ...$rowErrors);
                    continue;
                }

                $normalizedCode = mb_strtolower($row['teacher_code'], 'UTF-8');
                $normalizedEmail = mb_strtolower($row['email'], 'UTF-8');
                $seenCodes[$normalizedCode] = $lineNumber;
                $seenEmails[$normalizedEmail] = $lineNumber;

                $departmentId = null;
                if ($row['department_code'] !== '') {
                    $department = $departmentsByCode->get(mb_strtolower($row['department_code'], 'UTF-8'));
                    $departmentId = $department?->id;
                }

                $rows[] = [
                    'line_number' => $lineNumber,
                    'teacher_code' => $row['teacher_code'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'department_id' => $departmentId,
                    'status' => $row['status'] === '' ? 'active' : mb_strtolower($row['status'], 'UTF-8'),
                ];
            }

            if ($dataRowCount === 0) {
                $errors[] = 'File CSV không có dòng giáo viên nào.';
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

        foreach (['teacher_code', 'name', 'email', 'department_code'] as $requiredHeader) {
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

        foreach (['teacher_code', 'name', 'email', 'department_code', 'status'] as $column) {
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
     * @param array<string, int> $seenEmails
     * @param \Illuminate\Support\Collection<string, Department> $departmentsByCode
     * @return array<int, string>
     */
    private function validateRow(array $row, int $lineNumber, array $seenCodes, array $seenEmails, $departmentsByCode): array
    {
        $errors = [];

        foreach ($row as $value) {
            if (!mb_check_encoding($value, 'UTF-8')) {
                return ["Dòng {$lineNumber}: dữ liệu phải sử dụng mã hóa UTF-8."];
            }
        }

        if ($row['teacher_code'] === '') {
            $errors[] = "Dòng {$lineNumber}: teacher_code là bắt buộc.";
        } elseif (mb_strlen($row['teacher_code']) > 255) {
            $errors[] = "Dòng {$lineNumber}: teacher_code không được dài quá 255 ký tự.";
        } else {
            $normalizedCode = mb_strtolower($row['teacher_code'], 'UTF-8');
            if (array_key_exists($normalizedCode, $seenCodes)) {
                $errors[] = "Dòng {$lineNumber}: mã giáo viên '{$row['teacher_code']}' bị trùng với dòng {$seenCodes[$normalizedCode]}.";
            }
        }

        if ($row['name'] === '') {
            $errors[] = "Dòng {$lineNumber}: name là bắt buộc.";
        } elseif (mb_strlen($row['name']) > 255) {
            $errors[] = "Dòng {$lineNumber}: name không được dài quá 255 ký tự.";
        }

        if ($row['email'] === '') {
            $errors[] = "Dòng {$lineNumber}: email là bắt buộc.";
        } elseif (mb_strlen($row['email']) > 255 || filter_var($row['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = "Dòng {$lineNumber}: email không hợp lệ.";
        } else {
            $normalizedEmail = mb_strtolower($row['email'], 'UTF-8');
            if (array_key_exists($normalizedEmail, $seenEmails)) {
                $errors[] = "Dòng {$lineNumber}: email '{$row['email']}' bị trùng với dòng {$seenEmails[$normalizedEmail]}.";
            }
        }

        if ($row['department_code'] === '') {
            $errors[] = "Dòng {$lineNumber}: department_code là bắt buộc.";
        } elseif (!$departmentsByCode->has(mb_strtolower($row['department_code'], 'UTF-8'))) {
            $errors[] = "Dòng {$lineNumber}: department_code '{$row['department_code']}' không tồn tại.";
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
        $rowsByEmail = [];
        foreach ($rows as $row) {
            $rowsByCode[mb_strtolower($row['teacher_code'], 'UTF-8')] = $row;
            $rowsByEmail[mb_strtolower($row['email'], 'UTF-8')] = $row;
        }

        $existingCodes = [];
        foreach (array_chunk(array_keys($rowsByCode), 500) as $codeChunk) {
            Teacher::query()
                ->whereIn(DB::raw('LOWER(teacher_code)'), $codeChunk)
                ->pluck('teacher_code')
                ->each(function (string $code) use (&$existingCodes): void {
                    $existingCodes[mb_strtolower($code, 'UTF-8')] = true;
                });
        }

        $existingEmails = [];
        foreach (array_chunk(array_keys($rowsByEmail), 500) as $emailChunk) {
            User::query()
                ->whereIn(DB::raw('LOWER(email)'), $emailChunk)
                ->pluck('email')
                ->each(function (string $email) use (&$existingEmails): void {
                    $existingEmails[mb_strtolower($email, 'UTF-8')] = true;
                });
        }

        $errors = [];
        foreach ($existingCodes as $code => $_) {
            $row = $rowsByCode[$code];
            $errors[] = "Dòng {$row['line_number']}: mã giáo viên '{$row['teacher_code']}' đã tồn tại.";
        }

        foreach ($existingEmails as $email => $_) {
            $row = $rowsByEmail[$email];
            $message = "Dòng {$row['line_number']}: email '{$row['email']}' đã tồn tại.";
            if (!in_array($message, $errors, true)) {
                $errors[] = $message;
            }
        }

        return $errors;
    }
}
