<?php

namespace App\Http\Requests\Admin;

use App\Enums\IconKey;
use Illuminate\Validation\Rule;

class ServiceRequest extends ContentRequest
{
    protected array $stringLists = ['deliverables', 'engagement_steps'];

    protected ?string $slugSource = 'title';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => $this->slugRules('services', $this->route('service'), 180),
            'summary' => ['required', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:10000'],
            'deliverables' => ['array', 'max:20'],
            'deliverables.*' => ['string', 'max:200'],
            'engagement_steps' => ['array', 'max:12'],
            'engagement_steps.*' => ['string', 'max:200'],
            'icon_key' => ['required', Rule::enum(IconKey::class)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ] + $this->seoRules();
    }
}
