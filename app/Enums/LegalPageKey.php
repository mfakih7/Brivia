<?php

namespace App\Enums;

enum LegalPageKey: string
{
    case Privacy = 'privacy';
    case Terms = 'terms';

    public function label(): string
    {
        return match ($this) {
            self::Privacy => 'Privacy policy',
            self::Terms => 'Terms of use',
        };
    }
}
