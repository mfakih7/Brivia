<?php

namespace App\Http\Requests\Admin;

class LegalPageRequest extends ContentRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:60000'],
            'version_label' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._\-]+$/'],
        ];
    }

    public function messages(): array
    {
        return ['version_label.regex' => 'Use letters, numbers, dots, dashes or underscores (for example 2026-10 or v1.0).'] + parent::messages();
    }
}
