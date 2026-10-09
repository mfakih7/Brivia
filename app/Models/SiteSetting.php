<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Singleton (id = 1). Public, non-secret settings only. */
#[Fillable([
    'business_name', 'tagline', 'email', 'phone', 'address', 'service_coverage', 'whatsapp_url', 'social_links',
    'budget_options', 'timeline_options', 'default_meta_title', 'default_meta_description', 'default_timezone', 'copyright_name',
])]
class SiteSetting extends Model
{
    use FlushesPublicCache;

    public const SOCIAL_NETWORKS = ['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'x' => 'X'];

    protected $attributes = [
        'business_name' => 'BRIVIA',
        'social_links' => '{}',
        'budget_options' => '[]',
        'timeline_options' => '[]',
        'default_timezone' => 'Asia/Beirut',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'budget_options' => 'array',
            'timeline_options' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::find(1) ?? tap(new static, fn (self $settings) => $settings->forceFill(['id' => 1])->save());
    }

    /** @return array<string, string> network => url, only filled entries. */
    public function filledSocialLinks(): array
    {
        return array_filter($this->social_links ?? [], fn ($url) => filled($url));
    }
}
