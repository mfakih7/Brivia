<?php

namespace App\Mail;

use App\Enums\NotificationKind;
use App\Models\NotificationDelivery;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Renders one outbox row from its minimal payload snapshot (sent synchronously by the job). */
class OutboxMail extends Mailable
{
    use Queueable;

    public function __construct(public NotificationDelivery $delivery) {}

    public function envelope(): Envelope
    {
        $ref = $this->delivery->payload['reference'] ?? '';
        $site = config('app.name');

        return new Envelope(subject: match ($this->delivery->kind) {
            NotificationKind::EnquiryAcknowledgement => "We received your enquiry ({$ref})",
            NotificationKind::EnquiryStaffAlert => "New enquiry {$ref}",
            NotificationKind::AppointmentAcknowledgement => "We received your consultation request ({$ref})",
            NotificationKind::AppointmentStaffAlert => "New consultation request {$ref}",
            NotificationKind::AppointmentConfirmed => "Your consultation is confirmed ({$ref})",
            NotificationKind::AppointmentRescheduled => "Your consultation has been rescheduled ({$ref})",
            NotificationKind::AppointmentDeclined => "About your consultation request ({$ref})",
            NotificationKind::AppointmentCancelled => "Your consultation has been cancelled ({$ref})",
            NotificationKind::AppointmentReminder => "Reminder: your consultation with {$site} ({$ref})",
        });
    }

    public function content(): Content
    {
        $p = $this->delivery->payload;
        $tz = $p['timezone'] ?? config('brivia.appointments.staff_timezone');
        $start = isset($p['start_utc']) ? CarbonImmutable::parse($p['start_utc']) : null;

        return new Content(markdown: 'mail.outbox', with: [
            'kind' => $this->delivery->kind,
            'p' => $p,
            'when' => $start ? LocalTime::format($start, $tz) : null,
            'preferred' => isset($p['preferred_utc']) ? LocalTime::format(CarbonImmutable::parse($p['preferred_utc']), $tz) : null,
        ]);
    }
}
