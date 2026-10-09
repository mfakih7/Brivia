<?php

namespace App\Models\Concerns;

use LogicException;

trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(fn () => throw new LogicException(static::class.' records are append-only.'));
        static::deleting(fn () => throw new LogicException(static::class.' records are append-only.'));
    }
}
