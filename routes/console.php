<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Every task is aligned to 5-minute boundaries because shared hosting (Namecheap) runs cron at
 * most every 5 minutes: `*\/5 * * * * php artisan schedule:run`. A task scheduled for a minute
 * that cron never fires on would be silently skipped, so none are.
 */

// Queues pending outbox emails that are due (including 24h reminders) or whose job was lost.
Schedule::command('brivia:notifications:dispatch')->everyFiveMinutes()->withoutOverlapping(10);

// Shared hosting has no persistent daemons: a bounded worker drains the database queue each run,
// stops when empty or after max_seconds (< 5 minutes), and never overlaps a previous run.
if (config('brivia.scheduler.queue_worker')) {
    Schedule::command('queue:work', [
        '--stop-when-empty',
        '--tries=3',
        '--max-time='.config('brivia.scheduler.queue_worker_max_seconds'),
        '--max-jobs=200',
        '--sleep=3',
    ])->everyFiveMinutes()->withoutOverlapping(10)->name('brivia-bounded-queue-worker');
}

// Removes images that were replaced or detached more than an hour ago (only database-known paths).
Schedule::command('brivia:media-cleanup')->dailyAt('03:10')->withoutOverlapping();
