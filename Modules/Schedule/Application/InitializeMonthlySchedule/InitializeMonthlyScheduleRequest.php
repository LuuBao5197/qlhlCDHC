<?php
namespace Modules\Schedule\Application\InitializeMonthlySchedule;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class InitializeMonthlyScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTrainingOffice() || $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2099',
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Phai chon thang',
            'year.required' => 'Phai chon nam',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        $today = Carbon::today();
        $selectedDate = Carbon::create($data['year'], $data['month'], 1)->startOfMonth();

        $strictNextMonthOnly = (bool) config('schedule.initialize_monthly_schedule.strict_next_month_only', true);
        $allowCurrentMonthForTest = (bool) config('schedule.initialize_monthly_schedule.allow_current_month_in_test', false);

        $currentMonthStart = $today->copy()->startOfMonth();
        $nextMonthStart = $today->copy()->addMonth()->startOfMonth();

        // Production mode: only exactly next month is allowed.
        if ($strictNextMonthOnly) {
            if (! $selectedDate->equalTo($nextMonthStart)) {
                throw ValidationException::withMessages([
                    'month' => "Chi duoc khoi tao dung thang ke tiep. Hom nay {$today->format('d/m/Y')}, thang hop le la {$nextMonthStart->format('m/Y')}.",
                ]);
            }

            return $data;
        }

        // Test mode: allow from current or next month based on config.
        $minimumAllowedDate = $allowCurrentMonthForTest ? $currentMonthStart : $nextMonthStart;
        if ($selectedDate->lt($minimumAllowedDate)) {
            throw ValidationException::withMessages([
                'month' => "Thang hop le phai tu {$minimumAllowedDate->format('m/Y')} tro di. Hom nay {$today->format('d/m/Y')}.",
            ]);
        }

        return $data;
    }
}
