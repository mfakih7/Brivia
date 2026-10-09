<?php

namespace App\Jobs;

use App\Enums\AppointmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\NotificationKind;
use App\Mail\OutboxMail;
use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one outbox row. Re-checks state under a row lock, suppresses messages made obsolete
 * by later schedule changes, records the outcome, and never touches business records.
 * Providers may accept a message and still time out, so delivery is at-least-once at best.
 */
class SendNotificationDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 10;

    public function __construct(public int $deliveryId) {}

    public function handle(): void
    {
        $delivery = DB::transaction(function () {
            $delivery = NotificationDelivery::with('appointment')->lockForUpdate()->find($this->deliveryId);

            if (! $delivery || $delivery->status !== DeliveryStatus::Pending) {
                return null;
            }
            if ($delivery->scheduled_for?->isFuture()) {
                return null; // the scheduled dispatcher sends it when due
            }
            if ($delivery->last_attempt_at && $delivery->last_attempt_at->gt(now()->subMinutes(2)) && $this->attempts() === 1) {
                return null; // another worker is already sending it
            }
            if ($reason = $this->obsoleteReason($delivery)) {
                $delivery->forceFill(['status' => DeliveryStatus::Suppressed, 'last_error' => $reason])->save();

                return null;
            }

            $delivery->forceFill(['attempts' => $delivery->attempts + 1, 'last_attempt_at' => now()])->save();

            return $delivery;
        });

        if (! $delivery) {
            return;
        }

        try {
            Mail::to($delivery->recipient_email)->send(new OutboxMail($delivery));
            $delivery->forceFill(['status' => DeliveryStatus::Sent, 'sent_at' => now(), 'last_error' => null])->save();
        } catch (Throwable $e) {
            $exhausted = $delivery->attempts >= config('brivia.notifications.max_attempts');
            $delivery->forceFill([
                'status' => $exhausted ? DeliveryStatus::Failed : DeliveryStatus::Pending,
                'last_error' => self::redact($e),
            ])->save();
            report($e);

            if (! $exhausted) {
                $this->release(60 * $delivery->attempts);
            }
        }
    }

    private function obsoleteReason(NotificationDelivery $delivery): ?string
    {
        $appointment = $delivery->appointment;

        if (! $appointment || ! $delivery->kind->isScheduleBound()) {
            return null;
        }

        if (($delivery->payload['schedule_version'] ?? null) !== $appointment->schedule_version) {
            return 'Superseded by a later schedule change.';
        }

        if ($appointment->status !== AppointmentStatus::Confirmed) {
            return 'Appointment is no longer confirmed.';
        }

        if ($delivery->kind === NotificationKind::AppointmentReminder && $appointment->confirmed_start_at_utc?->isPast()) {
            return 'Appointment already started.';
        }

        return null;
    }

    /** Error text for staff: no email addresses, no secrets, bounded length. */
    public static function redact(Throwable $e): string
    {
        $message = preg_replace('/[^\s@<>"]+@[^\s@<>"]+/', '[email]', $e->getMessage());
        $message = preg_replace('/(password|token|secret|key)=\S+/i', '$1=[redacted]', (string) $message);

        return mb_substr(class_basename($e).': '.$message, 0, 480);
    }
}
