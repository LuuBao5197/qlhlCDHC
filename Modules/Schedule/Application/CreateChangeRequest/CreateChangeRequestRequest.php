<?php

namespace Modules\Schedule\Application\CreateChangeRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Modules\Schedule\Application\AssignMonthlySchedule\MonthlyAssignmentScopeResolver;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\TeachingSupportRequest;

class CreateChangeRequestRequest extends FormRequest
{
    private const PAYLOAD_ACTION_SPLIT_FROM_MERGED_GROUP = 'split_from_merged_group';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isDepartmentStaff() || $user->isAdmin());
    }

    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        $selectedSlotsJson = $this->input('selected_slots_json');

        if (is_string($selectedSlotsJson) && trim($selectedSlotsJson) !== '') {
            $decoded = json_decode($selectedSlotsJson, true);

            if (is_array($decoded)) {
                $this->merge([
                    'selected_slots' => $decoded,
                ]);
            }
        }

        if (! is_array($this->input('selected_slots'))) {
            $slotIds = $this->input('slot_ids');

            if (is_string($slotIds)) {
                $slotIds = collect(explode(',', $slotIds))
                    ->map(static fn (string $id) => trim($id))
                    ->filter(static fn (string $id) => $id !== '')
                    ->map(static fn (string $id) => (int) $id)
                    ->filter(static fn (int $id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
            }

            $newPayload = json_decode((string) $this->input('new_payload_json', ''), true);

            if (is_array($slotIds) && is_array($newPayload) && $newPayload !== []) {
                $selectedSlots = collect($slotIds)
                    ->map(static fn ($id) => (int) $id)
                    ->filter(static fn (int $id) => $id > 0)
                    ->unique()
                    ->values()
                    ->map(static fn (int $id) => [
                        'slot_id' => $id,
                        'new_payload' => $newPayload,
                    ])
                    ->all();

                $this->merge([
                    'selected_slots' => $selectedSlots,
                ]);
            }
        }

        $selectedSlots = $this->input('selected_slots');
        if (is_array($selectedSlots) && $selectedSlots !== []) {
            $expandedSelectedSlots = $this->expandSelectedSlotsAcrossActiveMergeGroups($selectedSlots);
            if ($expandedSelectedSlots !== []) {
                $this->merge([
                    'selected_slots' => $expandedSelectedSlots,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'monthly_schedule_id' => ['required', 'integer', 'exists:monthly_schedules,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'apply_mode' => ['nullable', 'string', Rule::in(['all_or_none'])],
            'selected_slots' => ['required', 'array', 'min:1'],
            'selected_slots.*.slot_id' => ['required', 'integer', 'distinct', 'exists:schedule_slots,id'],
            'selected_slots.*.new_payload' => ['required', 'array'],
            'selected_slots.*.new_payload.teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'selected_slots.*.new_payload.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'selected_slots.*.new_payload.subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'selected_slots.*.new_payload.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'selected_slots.*.new_payload.assignment_type' => ['nullable', 'string', Rule::in([ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY])],
            'selected_slots.*.new_payload.action' => ['nullable', 'string', Rule::in([self::PAYLOAD_ACTION_SPLIT_FROM_MERGED_GROUP])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $selectedSlots = $this->input('selected_slots', []);
            $monthlyScheduleId = (int) $this->input('monthly_schedule_id', 0);
            $anchorMonthlySchedule = null;
            $allowedMonthlyScheduleIds = [];
            $departmentScope = null;

            if ($monthlyScheduleId > 0) {
                $anchorMonthlySchedule = MonthlySchedule::query()->find($monthlyScheduleId);

                if ($anchorMonthlySchedule) {
                    $allowedMonthlyScheduleIds = MonthlySchedule::query()
                        ->where('month', (int) $anchorMonthlySchedule->month)
                        ->where('year', (int) $anchorMonthlySchedule->year)
                        ->pluck('id')
                        ->map(static fn ($id) => (int) $id)
                        ->values()
                        ->all();

                    $departmentScope = $this->resolveDepartmentScope($anchorMonthlySchedule);
                    if ($this->user()?->isDepartmentStaff() && $departmentScope === null) {
                        $validator->errors()->add(
                            'monthly_schedule_id',
                            'Khong xac dinh duoc pham vi khoa hien tai de tao phieu thay doi.'
                        );
                    }
                }
            }

            $allowedNewPayloadKeys = [
                'teacher_id',
                'subject_id',
                'subject_lesson_id',
                'room_id',
                'assignment_type',
                'action',
                'content',
                'slot_status',
                'actual_content',
                'note',
            ];

            $forbiddenSlotMoveKeys = [
                'class_id',
                'date',
                'day_of_week',
                'period',
                'period_number',
            ];

            if (! is_array($selectedSlots)) {
                return;
            }

            $slotIds = collect($selectedSlots)
                ->pluck('slot_id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $slotsQuery = ScheduleSlot::query()
                ->with(['subjectModel.department', 'scheduleSlotGroup'])
                ->where('slot_type', 'subject')
                ->where('assignment_source', 'internal')
                ->whereDoesntHave('teachingSupportRequestItems', function ($query): void {
                    $query->whereHas('request', function ($requestQuery): void {
                        $requestQuery->whereIn('status', [
                            TeachingSupportRequest::STATUS_PENDING_PDT,
                            TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                            TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                        ]);
                    });
                })
                ->whereIn('id', $slotIds);

            if (is_array($departmentScope) && isset($departmentScope['department_id'])) {
                $slotsQuery->whereHas('subjectModel', function ($query) use ($departmentScope): void {
                    $query->where('department_id', (int) $departmentScope['department_id']);
                });
            }

            $slots = $slotsQuery->get()->keyBy('id');
            $this->validateActiveMergeGroupPayloadConsistency($validator, collect($selectedSlots), $slots);

            $teacherAssignments = [];
            $processedActiveGroups = [];

            $normalizeNullableNumber = static function ($value): ?int {
                if ($value === '' || $value === null || $value === false) {
                    return null;
                }

                if (! is_numeric($value)) {
                    return null;
                }

                return (int) $value;
            };

            foreach ($selectedSlots as $index => $item) {
                $payload = $item['new_payload'] ?? null;
                $slotId = isset($item['slot_id']) && is_numeric($item['slot_id'])
                    ? (int) $item['slot_id']
                    : 0;

                /** @var ScheduleSlot|null $slot */
                $slot = $slotId > 0 ? $slots->get($slotId) : null;
                $activeGroupId = $slot ? $this->getActiveScheduleSlotGroupId($slot) : null;

                if (
                    $slot === null
                    && $slotId > 0
                ) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.slot_id",
                        'Tiet hoc khong thuoc khoa hien tai hoac khong nam trong batch thang da chon.'
                    );
                }

                if (
                    $slot
                    && $allowedMonthlyScheduleIds !== []
                    && ! in_array((int) $slot->monthly_schedule_id, $allowedMonthlyScheduleIds, true)
                ) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.slot_id",
                        'Tiet hoc khong thuoc batch thang da chon.'
                    );
                }

                if (! is_array($payload)) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload",
                        'new_payload phai la JSON object hop le.'
                    );
                    continue;
                }

                if ($payload === []) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload",
                        'new_payload khong duoc de trong.'
                    );
                    continue;
                }

                if (array_is_list($payload)) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload",
                        'new_payload phai la JSON object, khong phai JSON array.'
                    );
                    continue;
                }

                if (
                    array_key_exists('teacher_id', $payload)
                    && ! in_array($payload['teacher_id'], [null, '', false], true)
                    && ! is_numeric($payload['teacher_id'])
                ) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload.teacher_id",
                        'teacher_id phai la so nguyen hop le hoac null.'
                    );
                }

                $hasAssignmentTypePayload = array_key_exists('assignment_type', $payload);
                $assignmentType = $hasAssignmentTypePayload
                    ? $this->normalizeAssignmentType($payload['assignment_type'])
                    : null;
                $isSplitFromMergedGroupAction = $this->isSplitFromMergedGroupAction($payload);
                if ($isSplitFromMergedGroupAction && $activeGroupId === null) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload.action",
                        'Chi tiet khong thuoc nhom ghep active nen khong the tach khoi nhom ghep.'
                    );
                }

                if ($assignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
                    if (
                        array_key_exists('teacher_id', $payload)
                        && ! in_array($payload['teacher_id'], [null, '', false], true)
                    ) {
                        $validator->errors()->add(
                            "selected_slots.{$index}.new_payload.teacher_id",
                            'Tiet tu nghien cuu khong duoc gan giang vien.'
                        );
                    }

                    if (
                        array_key_exists('subject_lesson_id', $payload)
                        && ! in_array($payload['subject_lesson_id'], [null, '', false], true)
                    ) {
                        $validator->errors()->add(
                            "selected_slots.{$index}.new_payload.subject_lesson_id",
                            'Tiet tu nghien cuu khong duoc chon bai hoc.'
                        );
                    }
                }

                $payloadKeys = array_keys($payload);
                $unknownKeys = array_values(array_diff($payloadKeys, $allowedNewPayloadKeys));
                if ($unknownKeys !== []) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload",
                        'new_payload chua truong khong duoc phep: ' . implode(', ', $unknownKeys)
                    );
                }

                $forbiddenKeys = array_values(array_intersect($payloadKeys, $forbiddenSlotMoveKeys));
                if ($forbiddenKeys !== []) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload",
                        'Khong duoc doi tiet/ngay/lop trong phieu nay. Truong bi chan: ' . implode(', ', $forbiddenKeys)
                    );
                }

                $meaningfulValues = collect($payload)
                    ->filter(static fn ($value) => ! ($value === null || $value === ''));

                if ($meaningfulValues->isEmpty()) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload",
                        'new_payload phai co it nhat mot gia tri thay doi co y nghia.'
                    );
                }

                if (! $slot) {
                    continue;
                }

                if (
                    $activeGroupId !== null
                    && ! $isSplitFromMergedGroupAction
                    && isset($processedActiveGroups[$activeGroupId])
                ) {
                    continue;
                }

                $effectiveAssignmentType = $hasAssignmentTypePayload
                    ? $assignmentType
                    : $this->normalizeAssignmentType($slot->assignment_type);
                if ($effectiveAssignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY) {
                    if ($activeGroupId !== null && ! $isSplitFromMergedGroupAction) {
                        $processedActiveGroups[$activeGroupId] = true;
                    }
                    continue;
                }

                $effectiveTeacherId = array_key_exists('teacher_id', $payload)
                    ? $normalizeNullableNumber($payload['teacher_id'])
                    : $normalizeNullableNumber($slot->teacher_id);

                if ($effectiveTeacherId === null) {
                    continue;
                }

                $date = $slot->date?->format('Y-m-d');
                $periodNumber = $slot->period_number;
                $collisionGroupId = $isSplitFromMergedGroupAction ? null : $activeGroupId;
                $assignmentKey = ($collisionGroupId !== null
                    ? 'group:' . $activeGroupId
                    : 'slot:' . $slot->id) . '|' . $effectiveTeacherId . '|' . $date . '|' . $periodNumber;

                if (isset($teacherAssignments[$assignmentKey])) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload.teacher_id",
                        'Giang vien bi trung ngay va tiet trong danh sach tiet duoc chon.'
                    );
                    continue;
                }

                $teacherAssignments[$assignmentKey] = true;

                $existingConflict = ScheduleSlot::query()
                    ->where('teacher_id', $effectiveTeacherId)
                    ->whereDate('date', $date)
                    ->where('period_number', $periodNumber)
                    ->where('id', '!=', $slot->id)
                    ->when($collisionGroupId !== null, function ($query) use ($collisionGroupId): void {
                        $query->where(function ($nested) use ($collisionGroupId): void {
                            $nested->whereNull('schedule_slot_group_id')
                                ->orWhere('schedule_slot_group_id', '!=', $collisionGroupId);
                        });
                    })
                    ->exists();

                if ($existingConflict) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload.teacher_id",
                        'Giang vien da co lich trung ngay va tiet. Khong the tao phieu thay doi.'
                    );
                }

                if ($activeGroupId !== null && ! $isSplitFromMergedGroupAction) {
                    $processedActiveGroups[$activeGroupId] = true;
                }
            }
        });
    }

    private function normalizeAssignmentType(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY ? $value : null;
    }

    private function resolveDepartmentScope(MonthlySchedule $anchorMonthlySchedule): ?array
    {
        $user = $this->user();
        if (! $user || ! $user->isDepartmentStaff()) {
            return null;
        }

        return app(MonthlyAssignmentScopeResolver::class)->resolve($anchorMonthlySchedule, $user);
    }

    private function validateActiveMergeGroupPayloadConsistency($validator, Collection $selectedSlots, Collection $slots): void
    {
        $groupEntries = [];

        foreach ($selectedSlots as $index => $item) {
            $slotId = isset($item['slot_id']) && is_numeric($item['slot_id']) ? (int) $item['slot_id'] : 0;
            if ($slotId <= 0) {
                continue;
            }

            /** @var ScheduleSlot|null $slot */
            $slot = $slots->get($slotId);
            $groupId = $slot ? $this->getActiveScheduleSlotGroupId($slot) : null;
            if ($groupId === null) {
                continue;
            }

            if ($this->isSplitFromMergedGroupAction(is_array($item['new_payload'] ?? null) ? $item['new_payload'] : null)) {
                continue;
            }

            $groupEntries[$groupId][] = [
                'index' => $index,
                'slot_id' => $slotId,
                'signature' => $this->payloadSignature(is_array($item['new_payload'] ?? null) ? $item['new_payload'] : null),
            ];
        }

        foreach ($groupEntries as $groupId => $entries) {
            $signatures = collect($entries)->pluck('signature')->unique();
            if ($signatures->count() <= 1) {
                continue;
            }

            foreach ($entries as $entry) {
                $validator->errors()->add(
                    'selected_slots.' . $entry['index'] . '.new_payload',
                    'Cac tiet trong nhom ghep active #' . $groupId . ' phai co cung mot noi dung thay doi.'
                );
            }
        }
    }

    private function getActiveScheduleSlotGroupId(ScheduleSlot $slot): ?int
    {
        if ($slot->scheduleSlotGroup?->status !== 'active') {
            return null;
        }

        return is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null;
    }

    private function payloadSignature(?array $payload): string
    {
        if ($payload === null) {
            return '';
        }

        ksort($payload);

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    /**
     * @param array<int, array<string, mixed>> $selectedSlots
     * @return array<int, array<string, mixed>>
     */
    private function expandSelectedSlotsAcrossActiveMergeGroups(array $selectedSlots): array
    {
        $monthlyScheduleId = (int) $this->input('monthly_schedule_id', 0);
        if ($monthlyScheduleId <= 0 || $selectedSlots === []) {
            return $selectedSlots;
        }

        $anchorMonthlySchedule = MonthlySchedule::query()->find($monthlyScheduleId);
        if (! $anchorMonthlySchedule) {
            return $selectedSlots;
        }

        $allowedMonthlyScheduleIds = MonthlySchedule::query()
            ->where('month', (int) $anchorMonthlySchedule->month)
            ->where('year', (int) $anchorMonthlySchedule->year)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($allowedMonthlyScheduleIds === []) {
            return $selectedSlots;
        }

        $departmentScope = $this->resolveDepartmentScope($anchorMonthlySchedule);
        $slotIds = collect($selectedSlots)
            ->pluck('slot_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($slotIds === []) {
            return $selectedSlots;
        }

        $selectedSlotsById = ScheduleSlot::query()
            ->with(['scheduleSlotGroup'])
            ->where('slot_type', 'subject')
            ->where('assignment_source', 'internal')
            ->whereIn('monthly_schedule_id', $allowedMonthlyScheduleIds)
            ->whereIn('id', $slotIds)
            ->when(is_array($departmentScope) && isset($departmentScope['department_id']), function ($query) use ($departmentScope): void {
                $query->whereHas('subjectModel', function ($nested) use ($departmentScope): void {
                    $nested->where('department_id', (int) $departmentScope['department_id']);
                });
            })
            ->get()
            ->keyBy('id');

        $groupPayloads = [];
        foreach ($selectedSlots as $item) {
            $slotId = isset($item['slot_id']) && is_numeric($item['slot_id']) ? (int) $item['slot_id'] : 0;
            if ($slotId <= 0) {
                continue;
            }

            $slot = $selectedSlotsById->get($slotId);
            $groupId = $slot ? $this->getActiveScheduleSlotGroupId($slot) : null;
            if ($groupId === null) {
                continue;
            }

            if ($this->isSplitFromMergedGroupAction(is_array($item['new_payload'] ?? null) ? $item['new_payload'] : null)) {
                continue;
            }

            if (! isset($groupPayloads[$groupId])) {
                $groupPayloads[$groupId] = is_array($item['new_payload'] ?? null) ? $item['new_payload'] : [];
            }
        }

        if ($groupPayloads === []) {
            return array_values($selectedSlots);
        }

        $activeGroupIds = array_keys($groupPayloads);
        $groupSlots = ScheduleSlot::query()
            ->with(['scheduleSlotGroup'])
            ->where('slot_type', 'subject')
            ->where('assignment_source', 'internal')
            ->whereIn('monthly_schedule_id', $allowedMonthlyScheduleIds)
            ->whereIn('schedule_slot_group_id', $activeGroupIds)
            ->get();

        $expandedSlots = collect($selectedSlots);
        $existingSlotIds = $expandedSlots
            ->pluck('slot_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($groupSlots as $slot) {
            $groupId = $this->getActiveScheduleSlotGroupId($slot);
            if ($groupId === null) {
                continue;
            }

            if (in_array((int) $slot->id, $existingSlotIds, true)) {
                continue;
            }

            $expandedSlots->push([
                'slot_id' => (int) $slot->id,
                'new_payload' => $groupPayloads[$groupId] ?? [],
            ]);
        }

        return $expandedSlots
            ->unique('slot_id')
            ->values()
            ->all();
    }

    private function isSplitFromMergedGroupAction(?array $payload): bool
    {
        return ($payload['action'] ?? null) === self::PAYLOAD_ACTION_SPLIT_FROM_MERGED_GROUP;
    }
}
