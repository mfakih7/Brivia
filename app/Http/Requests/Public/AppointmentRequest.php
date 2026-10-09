<?php

namespace App\Http\Requests\Public;

use DateTimeZone;
use Illuminate\Validation\Rule;

class AppointmentRequest extends VisitorFormRequest
{
    public function rules(): array
    {
        return $this->contactRules() + [
            'consultation_type' => ['required', 'string', 'max:140'],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'preferred_date' => ['required', 'date_format:Y-m-d'],
            'preferred_time' => ['required', 'date_format:H:i'],
            'alternate_date' => ['nullable', 'required_with:alternate_time', 'date_format:Y-m-d'],
            'alternate_time' => ['nullable', 'required_with:alternate_date', 'date_format:H:i'],
            'summary' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'timezone.in' => 'Choose your timezone from the list.',
            'summary.min' => 'Please tell us a little more (at least 20 characters).',
            'alternate_date.required_with' => 'Add a date for the alternative time, or clear the time.',
            'alternate_time.required_with' => 'Add a time for the alternative date, or clear the date.',
        ];
    }
}
