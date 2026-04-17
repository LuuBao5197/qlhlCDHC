<?php
namespace Modules\Schedule\Application\InitializeMonthlySchedule;
use Illuminate\Foundation\Http\FormRequest;

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

        // Validate: Can only initialize future months (not current or past)
        $today = \Carbon\Carbon::today();
        $nextMonth = $today->copy()->addMonth()->month;
        $nextYear = $today->copy()->addMonth()->year;

        $selectedDate = \Carbon\Carbon::create($data['year'], $data['month'], 1);
        if ($selectedDate->lt($today->copy()->addMonth()->startOfMonth())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'month' => "Chi co the khoi tao nam va cac thang sau. Hien tai la {$today->format('d/m/Y')}.",
            ]);
        }

        return $data;
    }
}
