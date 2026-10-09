<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Singleton (id = 1). CTA targets are allowlisted named routes only. */
#[Fillable([
    'hero_eyebrow', 'hero_headline', 'hero_subtitle', 'primary_cta_label', 'primary_cta_route',
    'secondary_cta_label', 'secondary_cta_route', 'credibility_items', 'services_heading', 'projects_heading',
    'packages_heading', 'about_heading', 'about_body', 'process_heading', 'process_steps',
    'final_cta_heading', 'final_cta_body', 'section_visibility',
])]
class HomepageContent extends Model
{
    use FlushesPublicCache;

    protected $table = 'homepage_content';

    public const CTA_ROUTES = [
        'contact' => 'Contact',
        'consultation' => 'Book a consultation',
        'projects.index' => 'Projects',
        'services.index' => 'Services',
        'packages' => 'Packages',
        'about' => 'About us',
    ];

    public const SECTIONS = [
        'services' => 'Services',
        'projects' => 'Featured projects',
        'packages' => 'Package preview',
        'about' => 'About teaser',
        'process' => 'Delivery process',
        'final_cta' => 'Final call to action',
    ];

    protected $attributes = [
        'hero_headline' => 'The bridge from idea to product.',
        'primary_cta_label' => 'Discuss Your Project',
        'primary_cta_route' => 'contact',
        'credibility_items' => '[]',
        'process_steps' => '[]',
        'section_visibility' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'credibility_items' => 'array',
            'process_steps' => 'array',
            'section_visibility' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::find(1) ?? tap(new static, fn (self $content) => $content->forceFill(['id' => 1])->save());
    }

    public function showsSection(string $section): bool
    {
        return (bool) ($this->section_visibility[$section] ?? true);
    }
}
