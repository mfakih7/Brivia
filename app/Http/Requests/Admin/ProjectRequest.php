<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkOrigin;
use Illuminate\Validation\Rule;

class ProjectRequest extends ContentRequest
{
    protected array $stringLists = ['technologies'];

    protected ?string $slugSource = 'title';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => $this->slugRules('projects', $this->route('project'), 200),
            'category_id' => ['required', 'integer', 'exists:project_categories,id'],
            'summary' => ['required', 'string', 'max:500'],
            'client_display_name' => ['nullable', 'string', 'max:160'],
            'work_origin' => ['required', Rule::enum(WorkOrigin::class)],
            'contribution' => ['nullable', 'string', 'max:5000'],
            'problem' => ['nullable', 'string', 'max:5000'],
            'approach' => ['nullable', 'string', 'max:5000'],
            'solution' => ['nullable', 'string', 'max:5000'],
            'outcomes' => ['nullable', 'string', 'max:5000'],
            'technologies' => ['array', 'max:20'],
            'technologies.*' => ['string', 'max:60'],
            'website_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'is_featured' => ['boolean'],
            'permission_confirmed' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'service_ids' => ['array', 'max:50'],
            'service_ids.*' => ['integer', 'distinct', 'exists:services,id'],
        ] + $this->seoRules();
    }
}
