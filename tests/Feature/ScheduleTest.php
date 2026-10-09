<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Facade;
use Tests\TestCase;

/** Shared hosting runs cron every 5 minutes, so every task must fire on 5-minute boundaries. */
class ScheduleTest extends TestCase
{
    /** @return array<string, string> command fragment => cron expression */
    private function events(): array
    {
        return collect(app(Schedule::class)->events())
            ->mapWithKeys(fn (Event $e) => [$e->command => $e->expression])
            ->all();
    }

    private function find(string $needle): ?string
    {
        foreach ($this->events() as $command => $expression) {
            if (str_contains((string) $command, $needle)) {
                return $expression;
            }
        }

        return null;
    }

    public function test_tasks_are_aligned_to_five_minute_cron(): void
    {
        $this->assertSame('*/5 * * * *', $this->find('brivia:notifications:dispatch'));
        $this->assertSame('10 3 * * *', $this->find('brivia:media-cleanup'));

        foreach ($this->events() as $command => $expression) {
            [$minute] = explode(' ', $expression);
            $this->assertTrue($minute === '*/5' || (ctype_digit($minute) && (int) $minute % 5 === 0), "{$command} ({$expression}) would be skipped by a 5-minute cron.");
        }
    }

    public function test_bounded_queue_worker_is_opt_in(): void
    {
        $this->assertNull($this->find('queue:work'), 'Local development uses a terminal worker.');
    }

    public function test_bounded_queue_worker_runs_when_enabled(): void
    {
        config(['brivia.scheduler.queue_worker' => true]);
        $this->app->forgetInstance(Schedule::class);
        Facade::clearResolvedInstances(); // the Schedule facade caches the previous instance
        require base_path('routes/console.php');

        $worker = collect(app(Schedule::class)->events())->first(fn (Event $e) => str_contains((string) $e->command, 'queue:work'));

        $this->assertNotNull($worker);
        $this->assertSame('*/5 * * * *', $worker->expression);
        $this->assertTrue($worker->withoutOverlapping);
        $this->assertStringContainsString('--stop-when-empty', $worker->command);
        $this->assertStringContainsString('--max-time=240', $worker->command);
    }
}
