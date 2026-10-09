<?php

namespace App\Support;

/** Random, non-sequential public reference (no ambiguous characters). Never exposes internal IDs. */
class PublicReference
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function generate(string $prefix): string
    {
        $chars = '';
        for ($i = 0; $i < 8; $i++) {
            $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $prefix.'-'.$chars;
    }
}
