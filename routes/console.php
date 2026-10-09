<?php

use Illuminate\Support\Facades\Schedule;

// Queues pending outbox emails that are due (including 24h reminders) or whose job was lost.
Schedule::command('brivia:notifications:dispatch')->everyMinute()->withoutOverlapping();

// Removes images that were replaced or detached more than an hour ago (only database-known paths).
Schedule::command('brivia:media-cleanup')->daily()->withoutOverlapping();
