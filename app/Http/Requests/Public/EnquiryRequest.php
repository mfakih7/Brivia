<?php

namespace App\Http\Requests\Public;

use App\Enums\EnquiryType;
use App\Support\PublicSite;
use Illuminate\Validation\Rule;

class EnquiryRequest extends VisitorFormRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // An unticked context checkbox submits "0".
        if ($this->input('project') === '0') {
            $this->merge(['project' => null]);
        }
    }

    public function rules(): array
    {
        $settings = PublicSite::settings();

        return $this->contactRules() + [
            'enquiry_type' => ['required', Rule::enum(EnquiryType::class)],
            'service' => ['nullable', 'string', 'max:200'],
            'package' => ['nullable', 'string', 'max:200'],
            'project' => ['nullable', 'string', 'max:200'],
            'budget' => ['nullable', 'string', Rule::in($settings->budget_options ?? [])],
            'timeline' => ['nullable', 'string', Rule::in($settings->timeline_options ?? [])],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + ['message.min' => 'Please tell us a little more (at least 20 characters).'];
    }
}
