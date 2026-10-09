<?php

namespace App\Support;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationKind;
use App\Jobs\SendNotificationDelivery;
use App\Models\Appointment;
use App\Models\Enquiry;
use App\Models\NotificationDelivery;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Transactional email outbox. Rows are written in the same transaction as the business
 * change; a job is dispatched only after commit. The scheduled dispatcher recovers any
 * pending row whose job was lost. event_key is unique, so retries never duplicate rows.
 */
class NotificationOutbox
{
    public function queue(NotificationKind $kind, string $recipient, array $payload, Enquiry|Appointment $record, string $eventKey, ?DateTimeInterface $scheduledFor = null): ?NotificationDelivery
    {
        if (NotificationDelivery::where('event_key', $eventKey)->exists()) {
            return null;
        }

        $delivery = new NotificationDelivery;
        $delivery->forceFill([
            'event_key' => $eventKey,
            'enquiry_id' => $record instanceof Enquiry ? $record->id : null,
            'appointment_id' => $record instanceof Appointment ? $record->id : null,
            'kind' => $kind,
            'recipient_email' => $recipient,
            'payload' => $payload,
            'status' => DeliveryStatus::Pending,
            'scheduled_for' => $scheduledFor,
        ])->save();

        if ($scheduledFor === null || $scheduledFor <= now()) {
            DB::afterCommit(fn () => SendNotificationDelivery::dispatch($delivery->id));
        }

        return $delivery;
    }

    /** Queues one staff alert per configured recipient (none when unconfigured; the dashboard warns). */
    public function queueStaffAlerts(NotificationKind $kind, array $payload, Enquiry|Appointment $record, string $eventPrefix): void
    {
        foreach (config('brivia.notifications.staff_recipients') as $recipient) {
            $this->queue($kind, $recipient, $payload, $record, $eventPrefix.':staff:'.substr(hash('sha256', strtolower($recipient)), 0, 16));
        }
    }

    /** Marks pending schedule-dependent messages for an appointment as obsolete. */
    public function suppressObsolete(Appointment $appointment, string $reason): void
    {
        NotificationDelivery::where('appointment_id', $appointment->id)
            ->where('status', DeliveryStatus::Pending->value)
            ->whereIn('kind', [NotificationKind::AppointmentConfirmed->value, NotificationKind::AppointmentRescheduled->value, NotificationKind::AppointmentReminder->value])
            ->get()
            ->filter(fn (NotificationDelivery $d) => ($d->payload['schedule_version'] ?? -1) < $appointment->schedule_version)
            ->each(fn (NotificationDelivery $d) => $d->forceFill(['status' => DeliveryStatus::Suppressed, 'last_error' => $reason])->save());
    }

    /** Staff action: put a failed delivery back in the queue. */
    public function retry(NotificationDelivery $delivery): bool
    {
        if ($delivery->status !== DeliveryStatus::Failed) {
            return false;
        }

        $delivery->forceFill(['status' => DeliveryStatus::Pending, 'attempts' => 0, 'last_attempt_at' => null])->save();
        SendNotificationDelivery::dispatch($delivery->id);

        return true;
    }
}
