<?php

namespace Modules\Training\Application\TeacherEvaluation\SubmitTeacherSlotEvaluation;

use App\Support\AdminBackfillContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Teacher;

class SubmitTeacherSlotEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->isTeacher() || ($user->isAdmin() && $this->boolean('admin_backfill'));
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'teacher_id' => ['required_if:admin_backfill,1', 'nullable', 'integer', 'exists:teachers,id'],
            'slots' => ['nullable', 'array'],
            'slots.*.attendance_count' => ['nullable', 'integer', 'min:0'],
            'slots.*.absent_count' => ['nullable', 'integer', 'min:0'],
            'slots.*.rating_level' => ['required', 'in:tot,kha,trung_binh,yeu'],
            'slots.*.comment' => ['required', 'string', 'min:30', 'max:2000'],
        ] + AdminBackfillContext::rules();
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Ngày không được để trống.',
            'date.date' => 'Ngày không hợp lệ.',
            'teacher_id.required_if' => 'Vui lòng chọn giáo viên cần bổ sung đánh giá.',
            'teacher_id.exists' => 'Giáo viên được chọn không tồn tại.',
            'slots.*.attendance_count.integer' => 'Quân số phải là số nguyên.',
            'slots.*.attendance_count.min' => 'Quân số không được âm.',
            'slots.*.absent_count.integer' => 'Số vắng phải là số nguyên.',
            'slots.*.absent_count.min' => 'Số vắng không được âm.',
            'slots.*.rating_level.required' => 'Vui lòng chọn xếp loại tiết học.',
            'slots.*.rating_level.in' => 'Xếp loại tiết học không hợp lệ.',
            'slots.*.comment.required' => 'Vui lòng nhập nhận xét tiết học.',
            'slots.*.comment.min' => 'Nhận xét tiết học tối thiểu 30 ký tự.',
            'slots.*.comment.max' => 'Nhận xét tối đa 2000 ký tự.',
        ] + AdminBackfillContext::messages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $teacher = AdminBackfillContext::isActive($this)
                ? Teacher::query()->find($this->input('teacher_id'))
                : $this->resolveTeacherFromUser($user?->id, $user?->employee_code, $user?->name);

            if ($teacher === null) {
                $validator->errors()->add('slots', 'Không tìm thấy hồ sơ giáo viên tương ứng với tài khoản đăng nhập.');
                return;
            }

            $slotsInput = $this->input('slots', []);
            if (!is_array($slotsInput) || $slotsInput === []) {
                return;
            }

            foreach ($slotsInput as $index => $slotData) {
                if (!is_array($slotData)) {
                    continue;
                }

                $attendanceRaw = $slotData['attendance_count'] ?? null;
                $absentRaw = $slotData['absent_count'] ?? null;

                if ($attendanceRaw === '' || $absentRaw === '' || $attendanceRaw === null || $absentRaw === null) {
                    continue;
                }

                if (!is_numeric($attendanceRaw) || !is_numeric($absentRaw)) {
                    continue;
                }

                $attendanceCount = (int) $attendanceRaw;
                $absentCount = (int) $absentRaw;

                if ($absentCount > $attendanceCount) {
                    $validator->errors()->add("slots.$index.absent_count", 'Số vắng không được lớn hơn quân số.');
                }
            }

            $slotIds = collect(array_keys($slotsInput))
                ->filter(static fn ($id) => is_numeric($id))
                ->map(static fn ($id) => (int) $id)
                ->values();

            if ($slotIds->isEmpty()) {
                return;
            }

            $allowedCount = ScheduleSlot::query()
                ->where('teacher_id', $teacher->id)
                ->whereDate('date', (string) $this->input('date'))
                ->whereIn('id', $slotIds)
                ->count();

            if ($allowedCount !== $slotIds->count()) {
                $validator->errors()->add('slots', 'Bạn chỉ được đánh giá các tiết học do chính bạn phụ trách.');
            }
        });
    }

    private function resolveTeacherFromUser(?int $userId, ?string $employeeCode, ?string $name): ?Teacher
    {
        if ($userId !== null) {
            $teacher = Teacher::query()
                ->where('user_id', $userId)
                ->first();

            if ($teacher !== null) {
                return $teacher;
            }
        }

        if ($employeeCode !== null && $employeeCode !== '') {
            $teacher = Teacher::query()
                ->where('teacher_code', $employeeCode)
                ->first();

            if ($teacher !== null) {
                return $teacher;
            }
        }

        if ($name === null || trim($name) === '') {
            return null;
        }

        return Teacher::query()
            ->where('name', $name)
            ->first();
    }
}
