<?php

namespace App\Http\Requests\Admin;

use App\Models\HomepageContent;
use Illuminate\Validation\Rule;

class HomepageRequest extends ContentRequest
{
    protected array $stringLists = ['credibility_items'];

    protected array $pairLists = ['process_steps'];

    public function rules(): array
    {
        $routes = array_keys(HomepageContent::CTA_ROUTES);
        $rules = [
            'hero_eyebrow' => ['nullable', 'string', 'max:80'],
            'hero_headline' => ['required', 'string', 'max:160'],
            'hero_subtitle' => ['nullable', 'string', 'max:300'],
            'primary_cta_label' => ['required', 'string', 'max:40'],
            'primary_cta_route' => ['required', Rule::in($routes)],
            'secondary_cta_label' => ['nullable', 'required_with:secondary_cta_route', 'string', 'max:40'],
            'secondary_cta_route' => ['nullable', 'required_with:secondary_cta_label', Rule::in($routes)],
            'credibility_items' => ['array', 'max:5'],
            'credibility_items.*' => ['string', 'max:60'],
            'services_heading' => ['nullable', 'string', 'max:160'],
            'projects_heading' => ['nullable', 'string', 'max:160'],
            'packages_heading' => ['nullable', 'string', 'max:160'],
            'about_heading' => ['nullable', 'string', 'max:160'],
            'about_body' => ['nullable', 'string', 'max:2000'],
            'process_heading' => ['nullable', 'string', 'max:160'],
            'process_steps' => ['array', 'max:8'],
            'process_steps.*.title' => ['required', 'string', 'max:80'],
            'process_steps.*.body' => ['nullable', 'string', 'max:400'],
            'final_cta_heading' => ['nullable', 'string', 'max:160'],
            'final_cta_body' => ['nullable', 'string', 'max:300'],
            'section_visibility' => ['array:'.implode(',', array_keys(HomepageContent::SECTIONS))],
        ];

        foreach (array_keys(HomepageContent::SECTIONS) as $section) {
            $rules["section_visibility.{$section}"] = ['boolean'];
        }

        return $rules;
    }
}
