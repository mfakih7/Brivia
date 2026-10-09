<?php

namespace Tests\Unit;

use App\Support\LocalTime;
use PHPUnit\Framework\TestCase;

class LocalTimeTest extends TestCase
{
    public function test_regular_times_convert_with_the_timezone_database(): void
    {
        [$status, $utc] = LocalTime::resolve('2026-10-20', '10:00', 'Asia/Beirut');
        $this->assertSame(LocalTime::VALID, $status);
        $this->assertSame('2026-10-20 07:00', $utc->format('Y-m-d H:i'), 'Beirut is UTC+3 in October (summer time).');

        [, $winter] = LocalTime::resolve('2026-12-01', '10:00', 'Asia/Beirut');
        $this->assertSame('2026-12-01 08:00', $winter->format('Y-m-d H:i'), 'Beirut is UTC+2 in winter; never a fixed offset.');

        [, $ny] = LocalTime::resolve('2026-07-01', '09:30', 'America/New_York');
        $this->assertSame('2026-07-01 13:30', $ny->format('Y-m-d H:i'));
    }

    public function test_nonexistent_spring_forward_times_are_rejected(): void
    {
        $this->assertSame(LocalTime::NONEXISTENT, LocalTime::resolve('2027-03-28', '02:30', 'Europe/Berlin')[0]);
        $this->assertSame(LocalTime::NONEXISTENT, LocalTime::resolve('2027-03-14', '02:15', 'America/New_York')[0]);
    }

    public function test_ambiguous_fall_back_times_are_rejected(): void
    {
        $this->assertSame(LocalTime::AMBIGUOUS, LocalTime::resolve('2026-10-25', '02:30', 'Europe/Berlin')[0]);
        $this->assertSame(LocalTime::AMBIGUOUS, LocalTime::resolve('2026-11-01', '01:30', 'America/New_York')[0]);
        $this->assertSame(LocalTime::VALID, LocalTime::resolve('2026-10-25', '03:30', 'Europe/Berlin')[0]);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->assertSame(LocalTime::INVALID, LocalTime::resolve('2026-02-30', '10:00', 'Asia/Beirut')[0]);
        $this->assertSame(LocalTime::INVALID, LocalTime::resolve('2026-10-20', '25:00', 'Asia/Beirut')[0]);
        $this->assertSame(LocalTime::INVALID, LocalTime::resolve('2026-10-20', '10:00', 'Mars/Olympus')[0]);
    }
}
