<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;

/**
 * Converts a wall-clock date/time in an IANA timezone to a UTC instant. Times that do not
 * exist (DST spring-forward gap) or occur twice (DST fall-back overlap) are rejected rather
 * than silently shifted. Offsets always come from the timezone database, never fixed values.
 */
class LocalTime
{
    public const VALID = 'valid';

    public const NONEXISTENT = 'nonexistent';

    public const AMBIGUOUS = 'ambiguous';

    public const INVALID = 'invalid';

    /** @return array{0: string, 1: ?CarbonImmutable} [status, utc instant] */
    public static function resolve(string $date, string $time, string $timezone): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! preg_match('/^\d{2}:\d{2}$/', $time) || ! Timezones::isValid($timezone)) {
            return [self::INVALID, null];
        }

        [$y, $m, $d] = array_map('intval', explode('-', $date));
        [$h, $i] = array_map('intval', explode(':', $time));
        if (! checkdate($m, $d, $y) || $h > 23 || $i > 59) {
            return [self::INVALID, null];
        }

        $target = sprintf('%04d-%02d-%02d %02d:%02d', $y, $m, $d, $h, $i);
        $zone = new DateTimeZone($timezone);
        $naive = gmmktime($h, $i, 0, $m, $d, $y); // the wall-clock reading interpreted as UTC

        // Every offset in force within ±2 days is a candidate; keep those that map back to the same wall-clock time.
        $offsets = collect($zone->getTransitions($naive - 2 * 86400, $naive + 2 * 86400))->pluck('offset')->unique();
        $instants = $offsets
            ->map(fn (int $offset) => $naive - $offset)
            ->filter(fn (int $ts) => (new \DateTimeImmutable('@'.$ts))->setTimezone($zone)->format('Y-m-d H:i') === $target)
            ->unique()->values();

        return match ($instants->count()) {
            0 => [self::NONEXISTENT, null],
            1 => [self::VALID, CarbonImmutable::createFromTimestampUTC($instants->first())],
            default => [self::AMBIGUOUS, null],
        };
    }

    /** Resolves or throws a field-specific, helpful validation error. */
    public static function toUtcOrFail(string $date, string $time, string $timezone, string $field): CarbonImmutable
    {
        [$status, $utc] = self::resolve($date, $time, $timezone);

        return $utc ?? throw ValidationException::withMessages([$field => match ($status) {
            self::NONEXISTENT => 'That time does not exist in the selected timezone because the clocks change (daylight saving). Please choose another time.',
            self::AMBIGUOUS => 'That time happens twice in the selected timezone because the clocks go back (daylight saving). Please choose another time.',
            default => 'Enter a valid date and time.',
        }]);
    }

    /** "Tuesday 14 October 2026, 10:00 (Asia/Beirut, UTC+03:00)" */
    public static function format(?CarbonImmutable $utc, string $timezone, bool $withZone = true): string
    {
        if (! $utc) {
            return '—';
        }

        $local = $utc->setTimezone($timezone);

        return $local->format('l j F Y, H:i').($withZone ? ' ('.$timezone.', UTC'.$local->format('P').')' : '');
    }
}
