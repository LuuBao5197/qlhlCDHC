<?php

namespace Modules\Schedule\Application\CreateChangeRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Schedule\Models\ScheduleSlot;

class CreateChangeRequestRequest extends FormRequest
{
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
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'monthly_schedule_id' => ['required', 'integer', 'exists:monthly_schedules,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'apply_mode' => ['nullable', 'string', Rule::in(['all_or_none', 'best_effort'])],
            'selected_slots' => ['required', 'array', 'min:1'],
            'selected_slots.*.slot_id' => ['required', 'integer', 'distinct', 'exists:schedule_slots,id'],
            'selected_slots.*.new_payload' => ['required', 'array'],
            'selected_slots.*.new_payload.teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'selected_slots.*.new_payload.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'selected_slots.*.new_payload.subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'selected_slots.*.new_payload.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $selectedSlots = $this->input('selected_slots', []);
            $monthlyScheduleId = (int) $this->input('monthly_schedule_id', 0);

            $allowedNewPayloadKeys = [
                'teacher_id',
                'subject_id',
                'subject_lesson_id',
                'room_id',
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

            $slots = ScheduleSlot::query()
                ->whereIn('id', $slotIds)
                ->get()
                ->keyBy('id');

            $teacherAssignments = [];

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

                if ($slot && $monthlyScheduleId > 0 && (int) $slot->monthly_schedule_id !== $monthlyScheduleId) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.slot_id",
                        'Tiet hoc khong thuoc lich thang da chon.'
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

                $effectiveTeacherId = array_key_exists('teacher_id', $payload)
                    ? $normalizeNullableNumber($payload['teacher_id'])
                    : $normalizeNullableNumber($slot->teacher_id);

                if ($effectiveTeacherId === null) {
                    continue;
                }

                $date = $slot->date?->format('Y-m-d');
                $periodNumber = $slot->period_number;
                $assignmentKey = $effectiveTeacherId . '|' . $date . '|' . $periodNumber;

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
                    ->exists();

                if ($existingConflict) {
                    $validator->errors()->add(
                        "selected_slots.{$index}.new_payload.teacher_id",
                        'Giang vien da co lich trung ngay va tiet. Khong the tao phieu thay doi.'
                    );
                }
            }
        });
    }
}
