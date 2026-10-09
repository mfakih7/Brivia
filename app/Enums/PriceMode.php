<?php

namespace App\Enums;

enum PriceMode: string
{
    case Quote = 'quote';
    case StartingFrom = 'starting_from';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Request a quote',
            self::StartingFrom => 'Starting from',
            self::Fixed => 'Fixed price',
        };
    }

    public function requiresAmount(): bool
    {
        return $this !== self::Quote;
    }
}
