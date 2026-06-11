<?php

namespace Modules\Schedule\Application\UpdateHolidayCalendar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayCalendarRequest extends FormRequest
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
        $id = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => [
                'required',
                'date',
                Rule::unique('holiday_calendars', 'date')->ignore($id),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
