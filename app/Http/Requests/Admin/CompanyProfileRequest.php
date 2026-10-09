<?php

namespace App\Http\Requests\Admin;

class CompanyProfileRequest extends ContentRequest
{
    protected array $pairLists = ['values'];

    public function rules(): array
    {
        return [
            'public_intro' => ['nullable', 'string', 'max:1000'],
            'story' => ['nullable', 'string', 'max:10000'],
            'mission' => ['nullable', 'string', 'max:1000'],
            'values' => ['array', 'max:8'],
            'values.*.title' => ['required', 'string', 'max:80'],
            'values.*.body' => ['nullable', 'string', 'max:400'],
        ];
    }

    public function messages(): array
    {
        return ['values.*.title.required' => 'Each value needs a title.'] + parent::messages();
    }
}
