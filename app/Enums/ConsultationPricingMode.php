<?php

namespace App\Enums;

enum ConsultationPricingMode: string
{
    case Free = 'free';
    case Quote = 'quote';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Quote => 'Quoted on request',
            self::Fixed => 'Fixed fee',
        };
    }
}
