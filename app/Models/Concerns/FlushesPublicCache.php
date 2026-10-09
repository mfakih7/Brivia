<?php

namespace App\Models\Concerns;

use App\Support\PublicContentCache;

/** Any write to public content invalidates cached public fragments. */
trait FlushesPublicCache
{
    public static function bootFlushesPublicCache(): void
    {
        static::saved(fn () => PublicContentCache::flush());
        static::deleted(fn () => PublicContentCache::flush());
    }
}
