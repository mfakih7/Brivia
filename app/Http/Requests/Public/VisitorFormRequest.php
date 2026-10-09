<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/** Shared visitor contact rules (spec 01 "Shared visitor form rules"). */
abstract class VisitorFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->only(['name', 'email', 'phone', 'company']))
            ->map(fn ($v) => is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v)->all());
    }

    protected function contactRules(): array
    {
        return [
            'submission_key' => ['required', 'uuid'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-.\s]{5,40}$/'],
            'company' => ['nullable', 'string', 'max:160'],
            'privacy' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'submission_key.*' => 'Your form session expired. Please submit the form again.',
            'privacy.accepted' => 'Please confirm you have read the privacy notice.',
            'phone.regex' => 'Use digits, spaces and + ( ) - . only, including your country code.',
            'email.email' => 'Enter a valid email address, like name@example.com.',
        ];
    }

    public function attributes(): array
    {
        return ['summary' => 'project summary'];
    }
}
