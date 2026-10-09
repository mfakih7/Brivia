<?php

namespace App\Support;

use App\Enums\LegalPageKey;
use App\Models\LegalPage;
use App\Models\SiteSetting;

/** Published-only data shared by every public page (header, footer, SEO defaults). */
class PublicSite
{
    public static function settings(): SiteSetting
    {
        return SiteSetting::current();
    }

    /** @return array<string, bool> legal key => published */
    public static function legalLinks(): array
    {
        return PublicContentCache::remember('legal-links', fn () => collect(LegalPageKey::cases())
            ->mapWithKeys(fn ($key) => [$key->value => (bool) LegalPage::query()->published()->where('key', $key->value)->exists()])
            ->all(), 600);
    }

    /** Public pages are indexable only in production; staging and local are always noindex. */
    public static function indexable(): bool
    {
        return app()->isProduction();
    }
}
