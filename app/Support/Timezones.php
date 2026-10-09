<?php

namespace App\Support;

use DateTimeZone;

class Timezones
{
    /** @return array<string, array<string, string>> region => [identifier => label] for a grouped select. */
    public static function grouped(): array
    {
        $groups = [];

        foreach (DateTimeZone::listIdentifiers() as $id) {
            [$region] = explode('/', $id, 2) + [1 => null];
            $groups[$region][$id] = str_replace(['_', '/'], [' ', ' / '], $id);
        }

        return $groups;
    }

    public static function isValid(?string $id): bool
    {
        return is_string($id) && in_array($id, DateTimeZone::listIdentifiers(), true);
    }
}
