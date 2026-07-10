<?php

namespace Modules\Schedule\Application\CreateHolidayRescheduleRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateHolidayRescheduleRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isTrainingOffice() || $user->isAdmin());
    }

    /**
     * Prepare input for validation.
     */
    protected function prepareForValidation(): void
    {
        $rawHolidayDates = (string) $this->input('holiday_dates_csv', '');

        $holidayDates = collect(explode(',', $rawHolidayDates))
            ->map(static fn (string $item) => trim($item))
            ->filter(static fn (string $item) => $item !== '')
            ->values()
            ->all();

        $this->merge([
            'holiday_dates' => $holidayDates,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'monthly_schedule_id' => ['required', 'integer', 'exists:monthly_schedules,id'],
            'holiday_start_date' => ['nullable', 'date'],
            'holiday_end_date' => ['nullable', 'date', 'after_or_equal:holiday_start_date'],
            'holiday_dates_csv' => ['nullable', 'string', 'max:5000'],
            'holiday_dates' => ['nullable', 'array'],
            'holiday_dates.*' => ['required', 'date'],
            'target_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'apply_mode' => ['nullable', 'string', Rule::in(['all_or_none'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasRange = $this->filled('holiday_start_date') || $this->filled('holiday_end_date');
            $hasExplicitDates = is_array($this->input('holiday_dates')) && count($this->input('holiday_dates')) > 0;

            if (! $hasRange && ! $hasExplicitDates) {
                $validator->errors()->add(
                    'holiday_dates_csv',
                    'Can nhap it nhat khoang ngay nghi hoac danh sach ngay nghi roi rac.'
                );
            }

            if ($this->filled('holiday_start_date') xor $this->filled('holiday_end_date')) {
                $validator->errors()->add(
                    'holiday_start_date',
                    'Can nhap day du ca ngay bat dau va ngay ket thuc cho khoang ngay nghi.'
                );
            }
        });
    }
}
