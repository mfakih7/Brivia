<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for published public content. Any content mutation bumps the
 * version so stale fragments are never served; old keys expire naturally.
 */
class PublicContentCache
{
    private const VERSION_KEY = 'public-content:version';

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function remember(string $key, Closure $callback, int $seconds = 3600): mixed
    {
        return Cache::remember('public-content:'.self::version().':'.$key, $seconds, $callback);
    }
}
