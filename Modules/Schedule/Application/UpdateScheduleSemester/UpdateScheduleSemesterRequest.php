<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\TrainingClass;

class UpdateScheduleSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && ($user->isTrainingOffice() || $user->isAdmin());
    }

    public function rules(): array
    {
        $planId = $this->route('id');
        return [
            'semester' => 'required|integer|min:1|max:2',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 10),
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:500',
            'class_name' => 'nullable|string|max:50',
            'selected_class_ids' => 'nullable|array',
            'selected_class_ids.*' => 'integer|exists:classes,id',
            'class_tab_rules' => 'nullable|array',
            'import_file' => 'nullable|array',
            'import_file.*' => 'file|mimes:csv,txt',
        ];
    }

    public function messages(): array
    {
        return [
            'semester.required' => 'Hoc ky la bat buoc.',
            'year.required' => 'Nam hoc la bat buoc.',
            'end_date.after_or_equal' => 'Ngay ket thuc phai lon hon hoac bang ngay bat dau.',
            'selected_class_ids.*.exists' => 'Lop duoc chon khong ton tai.',
            'import_file.*.mimes' => 'File import phai o dinh dang CSV hoac TXT.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('class_name')) {
            $this->merge([
                'class_name' => Str::upper(trim((string) $this->input('class_name', ''))),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $planId = $this->route('id');
            $plan = Plans::query()->find($planId);
            $trainingBatchId = $plan?->training_batch_id;
            $semester = $this->input('semester');
            $year = $this->input('year');

            if ($trainingBatchId) {
                if (is_numeric($semester) && is_numeric($year)) {
                    $duplicatePlanExists = Plans::query()
                        ->where('training_batch_id', (int) $trainingBatchId)
                        ->where('semester', (int) $semester)
                        ->where('year', (int) $year)
                        ->where('id', '!=', $planId)
                        ->exists();

                    if ($duplicatePlanExists) {
                        $validator->errors()->add(
                            'semester',
                            'Khoa dao tao cua ke hoach nay da co ke hoach hoc ky ' . $semester . ' nam ' . $year . '.'
                        );
                    }
                }

                $selectedClassIds = collect($this->input('selected_class_ids', []))
                    ->filter(fn($id) => $id !== null && $id !== '')
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($selectedClassIds->isNotEmpty()) {
                    $invalidClassCodes = TrainingClass::query()
                        ->whereIn('id', $selectedClassIds->all())
                        ->where(function ($query) use ($trainingBatchId) {
                            $query
                                ->whereNull('training_batch_id')
                                ->orWhere('training_batch_id', '!=', (int) $trainingBatchId);
                        })
                        ->pluck('code');

                    if ($invalidClassCodes->isNotEmpty()) {
                        $validator->errors()->add(
                            'selected_class_ids',
                            'Cac lop sau khong thuoc khoa dao tao cua ke hoach: ' . $invalidClassCodes->implode(', ') . '.'
                        );
                    }
                }
            }

            $validClassKeys = collect($this->input('selected_class_ids', []))
                ->filter(fn($id) => $id !== null && $id !== '')
                ->map(fn($id) => (string) $id)
                ->unique()
                ->values();

            $fallbackKey = $this->fallbackClassKey();
            if ($fallbackKey !== null) {
                $validClassKeys->push($fallbackKey);
            }

            $validClassKeys = $validClassKeys->unique()->values();

            if ($validClassKeys->isEmpty()) {
                $validator->errors()->add(
                    'selected_class_ids',
                    'Phai chon it nhat mot lop hoac nhap lop bo sung.'
                );
                return;
            }

            $rulesByClass = $this->normalizeRulesPayload($validator);
            if ($rulesByClass === null) {
                return;
            }

            $importFiles = $this->normalizeImportFilesPayload();
            $validClassLookup = array_fill_keys($validClassKeys->all(), true);

            foreach (array_keys($rulesByClass) as $classKey) {
                if (!isset($validClassLookup[(string) $classKey])) {
                    $validator->errors()->add(
                        'class_tab_rules',
                        "Du lieu quy tac khong hop le cho lop '{$this->classLabel((string)$classKey)}'."
                    );
                }
            }

            foreach (array_keys($importFiles) as $classKey) {
                if (!isset($validClassLookup[(string) $classKey])) {
                    $validator->errors()->add(
                        'import_file',
                        "File import khong hop le cho lop '{$this->classLabel((string)$classKey)}'."
                    );
                }
            }

            foreach ($validClassKeys as $classKey) {
                $classRules = $rulesByClass[$classKey] ?? [];
                $label = $this->classLabel((string) $classKey);
                $hasMeaningfulRule = false;
                $hasImportFile = array_key_exists((string) $classKey, $importFiles)
                    && $importFiles[(string) $classKey] !== null;
                $seenSlots = [];

                if (!is_array($classRules)) {
                    $validator->errors()->add(
                        'class_tab_rules',
                        "Quy tac cua lop '{$label}' phai la mot danh sach."
                    );
                    continue;
                }

                foreach ($classRules as $index => $rule) {
                    if (!is_array($rule)) {
                        $validator->errors()->add(
                            'class_tab_rules',
                            "Dong quy tac #" . ($index + 1) . " cua lop '{$label}' khong hop le."
                        );
                        continue;
                    }

                    if (!$this->ruleHasInput($rule)) {
                        continue;
                    }

                    $hasMeaningfulRule = true;
                    $ruleNumber = $index + 1;
                    $hasRuleError = false;

                    if (trim((string) ($rule['subject'] ?? '')) === '') {
                        $hasRuleError = true;
                        $validator->errors()->add(
                            'class_tab_rules',
                            "Dong quy tac #{$ruleNumber} cua lop '{$label}' phai nhap mon hoc."
                        );
                    }

                    $startDate = $rule['start_date'] ?? null;
                    $endDate = $rule['end_date'] ?? null;
                    if (!$this->isDateValue($startDate) || !$this->isDateValue($endDate)) {
                        $hasRuleError = true;
                        $validator->errors()->add(
                            'class_tab_rules',
                            "Dong quy tac #{$ruleNumber} cua lop '{$label}' phai co ngay bat dau va ngay ket thuc hop le."
                        );
                    } elseif (Carbon::parse($endDate)->lt(Carbon::parse($startDate))) {
                        $hasRuleError = true;
                        $validator->errors()->add(
                            'class_tab_rules',
                            "Dong quy tac #{$ruleNumber} cua lop '{$label}' co ngay ket thuc nho hon ngay bat dau."
                        );
                    }

                    $weekdays = $this->normalizeWeekdays($rule['weekdays'] ?? []);
                    if ($weekdays === []) {
                        $hasRuleError = true;
                        $validator->errors()->add(
                            'class_tab_rules',
                            "Dong quy tac #{$ruleNumber} cua lop '{$label}' phai chon it nhat mot thu hoc."
                        );
                    }

                    $periodFrom = $this->parsePeriodValue($rule['period_from'] ?? null);
                    $periodTo = $this->parsePeriodValue($rule['period_to'] ?? null);
                    if ($periodFrom === null || $periodTo === null) {
                        $hasRuleError = true;
                        $validator->errors()->add(
                            'class_tab_rules',
                            "Dong quy tac #{$ruleNumber} cua lop '{$label}' phai nhap tiet tu 1 den 9."
                        );
                    } elseif ($periodTo < $periodFrom) {
                        $hasRuleError = true;
                        $validator->errors()->add(
                            'class_tab_rules',
                            "Dong quy tac #{$ruleNumber} cua lop '{$label}' co tiet ket thuc nho hon tiet bat dau."
                        );
                    }

                    if ($hasRuleError) {
                        continue;
                    }

                    $start = Carbon::parse((string) $startDate)->startOfDay();
                    $end = Carbon::parse((string) $endDate)->endOfDay();

                    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                        $dayOfWeek = $date->dayOfWeekIso + 1;
                        if (!in_array($dayOfWeek, $weekdays, true)) {
                            continue;
                        }

                        for ($period = $periodFrom; $period <= $periodTo; $period++) {
                            $slotKey = implode('|', [$date->toDateString(), $period]);
                            if (isset($seenSlots[$slotKey])) {
                                $firstRuleNumber = $seenSlots[$slotKey] + 1;
                                $validator->errors()->add(
                                    'class_tab_rules',
                                    "Dong quy tac #{$ruleNumber} cua lop '{$label}' bi trung tiet voi dong #{$firstRuleNumber} ({$date->toDateString()}, tiet {$period})."
                                );
                            } else {
                                $seenSlots[$slotKey] = $index;
                            }
                        }
                    }
                }

                if (!$hasMeaningfulRule && !$hasImportFile) {
                    $validator->errors()->add(
                        'class_tab_rules',
                        "Lop '{$label}' can co it nhat mot quy tac hoac mot file CSV."
                    );
                }
            }
        });
    }

    private function fallbackClassKey(): ?string
    {
        $className = trim((string) $this->input('class_name', ''));
        if ($className === '') {
            return null;
        }

        $normalized = $this->normalizeClassCode($className);
        if ($normalized === '') {
            return null;
        }

        return 'name:' . $normalized;
    }

    private function normalizeRulesPayload($validator): ?array
    {
        $rawRules = $this->input('class_tab_rules', []);

        if ($rawRules === null || $rawRules === '' || $rawRules === []) {
            return [];
        }

        $decoded = is_string($rawRules) ? json_decode($rawRules, true) : $rawRules;
        if (!is_array($decoded)) {
            $validator->errors()->add('class_tab_rules', 'Du lieu quy tac lich khong hop le.');
            return null;
        }

        return $decoded;
    }

    private function normalizeImportFilesPayload(): array
    {
        $files = $this->file('import_file', []);

        return is_array($files) ? $files : [];
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

    private function parsePeriodValue(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $period = (int) $value;

        return $period >= 1 && $period <= 9 ? $period : null;
    }

    private function normalizeWeekdays(mixed $value): array
    {
        $weekdays = is_array($value)
            ? $value
            : preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return collect($weekdays)
            ->filter(fn($item) => is_numeric($item))
            ->map(fn($item) => (int) $item)
            ->filter(fn(int $item) => $item >= 2 && $item <= 8)
            ->unique()
            ->sort()
            ->values()
            ->all();
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

    private function classLabel(string $classKey): string
    {
        if (str_starts_with($classKey, 'name:')) {
            return trim((string) $this->input('class_name', '')) ?: substr($classKey, 5);
        }

        if (ctype_digit($classKey)) {
            return TrainingClass::query()->find($classKey)?->code ?? $classKey;
        }

        return $classKey;
    }

    private function normalizeClassCode(string $value): string
    {
        return Str::upper((string) preg_replace('/[^A-Z0-9_]/', '_', trim($value)));
    }
}
