<?php

namespace Modules\Schedule\Application\InitializeMonthlySchedule;

use App\Support\AdminBackfillContext;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
        ] + AdminBackfillContext::rules();
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Phai chon thang',
            'year.required' => 'Phai chon nam',
        ] + AdminBackfillContext::messages();
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (AdminBackfillContext::isActive($this)) {
                return;
            }

            $today = Carbon::today();
            $selectedDate = Carbon::create(
                (int) $this->input('year'),
                (int) $this->input('month'),
                1
            )->startOfMonth();

            $strictRollingWindow = (bool) config('schedule.initialize_monthly_schedule.strict_next_month_only', true);
            $allowCurrentMonthForTest = (bool) config('schedule.initialize_monthly_schedule.allow_current_month_in_test', false);

            $currentMonthStart = $today->copy()->startOfMonth();
            $nextMonthStart = $today->copy()->addMonth()->startOfMonth();

            // Production mode: allow backfilling the current month or preparing the next month.
            if ($strictRollingWindow) {
                if (! $selectedDate->betweenIncluded($currentMonthStart, $nextMonthStart)) {
                    $validator->errors()->add(
                        'month',
                        "Chi duoc khoi tao thang hien tai hoac thang ke tiep. Hom nay {$today->format('d/m/Y')}, cac thang hop le la {$currentMonthStart->format('m/Y')} va {$nextMonthStart->format('m/Y')}."
                    );
                }

                return;
            }

            // Relaxed mode: allow from current or next month based on config.
            $minimumAllowedDate = $allowCurrentMonthForTest ? $currentMonthStart : $nextMonthStart;
            if ($selectedDate->lt($minimumAllowedDate)) {
                $validator->errors()->add(
                    'month',
                    "Thang hop le phai tu {$minimumAllowedDate->format('m/Y')} tro di. Hom nay {$today->format('d/m/Y')}."
                );
            }
        }];
    }
}
