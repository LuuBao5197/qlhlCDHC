<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Subject;

class AssignMonthlyScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        if (!$user->isDepartmentStaff()) {
            return false;
        }

        $scheduleId = (int) $this->route('id');
        if ($scheduleId <= 0) {
            return true;
        }

        $monthlySchedule = MonthlySchedule::query()->with('trainingClass')->find($scheduleId);
        if (!$monthlySchedule) {
            return false;
        }

        $departmentId = $monthlySchedule->department_id ?? $monthlySchedule->trainingClass?->department_id;

        if ($departmentId === null || $user->department_id === null) {
            return true;
        }

        return (int) $departmentId === (int) $user->department_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.id' => ['required', 'integer', 'exists:schedule_slots,id'],
            'slots.*.teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'slots.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'slots.*.subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'slots.*.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'slots.*.content' => ['nullable', 'string', 'max:500'],
            'slots.*.note' => ['nullable', 'string', 'max:500'],
            'slots.*.slot_status' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $scheduleId = (int) $this->route('id');
            if ($scheduleId <= 0) {
                return;
            }

            $monthlySchedule = MonthlySchedule::query()
                ->with('trainingClass')
                ->find($scheduleId);

            if (!$monthlySchedule) {
                return;
            }

            $departmentId = $monthlySchedule->department_id ?? $monthlySchedule->trainingClass?->department_id;
            $departmentSubjectIds = [];

            if ($departmentId !== null) {
                $departmentSubjectIds = Subject::query()
                    ->where('department_id', $departmentId)
                    ->pluck('id')
                    ->all();
            }

            $slotIds = collect($this->input('slots', []))
                ->pluck('id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $scheduleSlots = ScheduleSlot::query()
                ->whereIn('id', $slotIds)
                ->get()
                ->keyBy('id');

            $teacherAssignments = [];

            foreach ($this->input('slots', []) as $index => $slot) {
                $slotId = isset($slot['id']) ? (int) $slot['id'] : null;
                if ($slotId === null || !$scheduleSlots->has($slotId)) {
                    continue;
                }

                $scheduleSlot = $scheduleSlots[$slotId];
                $subjectId = $slot['subject_id'] ?? null;

                if ($subjectId !== null && $departmentId !== null && !in_array((int) $subjectId, $departmentSubjectIds, true)) {
                    $validator->errors()->add(
                        "slots.{$index}.subject_id",
                        'Môn học được chọn không thuộc khoa phụ trách.'
                    );
                }

                $teacherId = $slot['teacher_id'] ?? null;
                if ($teacherId === null) {
                    continue;
                }

                $teacherId = (int) $teacherId;
                $date = $scheduleSlot->date?->format('Y-m-d');
                $period = $scheduleSlot->period_number;
                $conflictKey = "{$teacherId}_{$date}_{$period}";

                if (isset($teacherAssignments[$conflictKey])) {
                    $validator->errors()->add(
                        "slots.{$index}.teacher_id",
                        'Giáo viên này đã được phân công trùng ngày và tiết trong cùng một lần gửi.'
                    );
                }

                $teacherAssignments[$conflictKey] = true;

                $existingConflict = ScheduleSlot::query()
                    ->where('teacher_id', $teacherId)
                    ->whereDate('date', $date)
                    ->where('period_number', $period)
                    ->where('id', '!=', $slotId)
                    ->exists();

                if ($existingConflict) {
                    $validator->errors()->add(
                        "slots.{$index}.teacher_id",
                        'Giáo viên này đã có lịch dạy trùng ngày và tiết khác.'
                    );
                }
            }
        });
    }

    /**
     * Extra validation constraints.
     */
    public function messages(): array
    {
        return [
            'slots.required' => 'Khong co du lieu tiet hoc de cap nhat.',
            'slots.*.id.exists' => 'Co tiet hoc khong ton tai trong he thong.',
            'slots.*.teacher_id.exists' => 'Giang vien duoc chon khong hop le.',
            'slots.*.subject_id.exists' => 'Mon hoc duoc chon khong hop le.',
            'slots.*.room_id.exists' => 'Phong hoc duoc chon khong hop le.',
        ];
    }
}
