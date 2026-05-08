<?php

namespace Modules\Training\Application\TeacherEvaluation\SubmitDailyLog;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitDailyLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user->isTrainingOffice();
    }

    public function rules(): array
    {
        return [
            'date'                   => ['required', 'date'],

            // Overall daily evaluation (Section 2 of the form)
            'training_plan_comment'  => ['nullable', 'string', 'max:2000'],
            'regulation_comment'     => ['nullable', 'string', 'max:2000'],
            'facility_comment'       => ['nullable', 'string', 'max:2000'],
            'followup_comment'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'                        => 'Ngày không được để trống.',
            'date.date'                            => 'Ngày không hợp lệ.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dateInput = (string) $this->input('date');

            if ($dateInput === '') {
                return;
            }

            $isTestMode = app()->environment(['local', 'testing']);
            $isToday = Carbon::parse($dateInput)->isSameDay(Carbon::today());

            if (! $isToday && ! $isTestMode) {
                $validator->errors()->add('date', 'Chỉ được chỉnh sửa và lưu nhật ký cho ngày hiện tại.');
            }
        });
    }
}
