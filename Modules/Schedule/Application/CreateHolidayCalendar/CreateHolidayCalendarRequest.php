<?php

namespace Modules\Schedule\Application\CreateHolidayCalendar;

use Illuminate\Foundation\Http\FormRequest;

class CreateHolidayCalendarRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'unique:holiday_calendars,date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
