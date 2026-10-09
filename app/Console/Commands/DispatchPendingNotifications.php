<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Jobs\SendNotificationDelivery;
use App\Models\NotificationDelivery;
use Illuminate\Console\Command;

/**
 * Recovery dispatcher (scheduled every minute): queues pending outbox rows that are due and
 * not already in flight, including reminders whose scheduled time has arrived.
 */
class DispatchPendingNotifications extends Command
{
    protected $signature = 'brivia:notifications:dispatch';

    protected $description = 'Queue due or orphaned pending notification deliveries';

    public function handle(): int
    {
        $count = 0;

        NotificationDelivery::where('status', DeliveryStatus::Pending->value)
            ->where(fn ($q) => $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()))
            ->where(fn ($q) => $q->whereNull('last_attempt_at')->orWhere('last_attempt_at', '<', now()->subMinutes(5)))
            ->where(fn ($q) => $q->whereNotNull('scheduled_for')->orWhere('created_at', '<', now()->subMinute()))
            ->orderBy('id')
            ->limit(200)
            ->pluck('id')
            ->each(function (int $id) use (&$count) {
                SendNotificationDelivery::dispatch($id);
                $count++;
            });

        $this->info("Queued {$count} notification(s).");

        return self::SUCCESS;
    }
}
