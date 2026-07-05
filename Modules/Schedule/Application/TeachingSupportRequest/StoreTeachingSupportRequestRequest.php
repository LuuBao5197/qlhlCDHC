<?php

namespace Modules\Schedule\Application\TeachingSupportRequest;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Schedule\Models\ScheduleSlot;

class StoreTeachingSupportRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $requestingDepartmentId = $this->input('requesting_department_id');
        if (is_string($requestingDepartmentId) && trim($requestingDepartmentId) === '') {
            $requestingDepartmentId = null;
        }
        if ($requestingDepartmentId === null && $this->user()?->isDepartmentStaff()) {
            $requestingDepartmentId = $this->user()?->department_id;
        }

        $requestNote = $this->input('request_note');
        if (is_string($requestNote)) {
            $requestNote = trim($requestNote);
            if ($requestNote === '') {
                $requestNote = null;
            }
        }

        $slotIds = $this->input('slot_ids');
        if (is_string($slotIds)) {
            $decoded = json_decode($slotIds, true);
            if (is_array($decoded)) {
                $slotIds = $decoded;
            } else {
                $slotIds = collect(explode(',', $slotIds))
                    ->map(static fn (string $slotId): string => trim($slotId))
                    ->filter(static fn (string $slotId): bool => $slotId !== '')
                    ->values()
                    ->all();
            }
        } elseif (is_numeric($slotIds)) {
            $slotIds = [(int) $slotIds];
        }

        $this->merge([
            'requesting_department_id' => $requestingDepartmentId,
            'request_note' => $requestNote,
            'slot_ids' => $slotIds,
        ]);
    }

    public function rules(): array
    {
        return [
            'requesting_department_id' => ['required', 'integer', 'exists:departments,id'],
            'supporting_department_id' => ['required', 'integer', 'exists:departments,id'],
            'request_note' => ['nullable', 'string', 'max:2000'],
            'slot_ids' => ['required', 'array', 'min:1'],
            'slot_ids.*' => ['integer', 'distinct', 'exists:schedule_slots,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'requesting_department_id.required' => 'Không xác định được khoa gửi yêu cầu.',
            'requesting_department_id.integer' => 'Khoa gửi yêu cầu không hợp lệ.',
            'requesting_department_id.exists' => 'Khoa gửi yêu cầu không tồn tại.',
            'supporting_department_id.required' => 'Vui lòng chọn khoa hỗ trợ.',
            'supporting_department_id.integer' => 'Khoa hỗ trợ không hợp lệ.',
            'supporting_department_id.exists' => 'Khoa hỗ trợ không tồn tại.',
            'request_note.string' => 'Ghi chú phải là chuỗi văn bản hợp lệ.',
            'request_note.max' => 'Ghi chú không được vượt quá 2000 ký tự.',
            'slot_ids.required' => 'Vui lòng chọn ít nhất 1 tiết để đề nghị hỗ trợ.',
            'slot_ids.array' => 'Danh sách tiết không hợp lệ.',
            'slot_ids.min' => 'Vui lòng chọn ít nhất 1 tiết để đề nghị hỗ trợ.',
            'slot_ids.*.integer' => 'Mã tiết không hợp lệ.',
            'slot_ids.*.distinct' => 'Danh sách tiết không được trùng nhau.',
            'slot_ids.*.exists' => 'Có tiết không tồn tại.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $user = $this->user();
            if (! $user || ! $user->isDepartmentStaff()) {
                return;
            }

            $requestingDepartmentId = (int) ($this->input('requesting_department_id') ?? 0);
            if ($requestingDepartmentId !== (int) $user->department_id) {
                $validator->errors()->add(
                    'requesting_department_id',
                    'Khoa gửi yêu cầu không khớp với tài khoản đang đăng nhập.'
                );
            }

            $slotIds = collect($this->input('slot_ids', []))
                ->filter(static fn ($slotId): bool => is_numeric($slotId))
                ->map(static fn ($slotId): int => (int) $slotId)
                ->values()
                ->all();

            if ($slotIds === []) {
                return;
            }

            $slots = ScheduleSlot::query()
                ->with('subjectLesson')
                ->whereIn('id', $slotIds)
                ->get()
                ->keyBy('id');

            foreach ($slotIds as $slotId) {
                $slot = $slots->get($slotId);
                if (! $slot instanceof ScheduleSlot) {
                    continue;
                }

                $lessonTitle = trim((string) ($slot->subjectLesson?->title ?? ''));
                if ($slot->subject_lesson_id === null || $lessonTitle === '') {
                    $validator->errors()->add(
                        'slot_ids',
                        sprintf('Tiết #%d chưa có bài học rõ ràng nên không thể gửi yêu cầu hỗ trợ.', $slotId)
                    );
                    break;
                }
            }
        });
    }
}
