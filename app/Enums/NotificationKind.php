<?php

namespace App\Enums;

enum NotificationKind: string
{
    case EnquiryAcknowledgement = 'enquiry_acknowledgement';
    case EnquiryStaffAlert = 'enquiry_staff_alert';
    case AppointmentAcknowledgement = 'appointment_acknowledgement';
    case AppointmentStaffAlert = 'appointment_staff_alert';
    case AppointmentConfirmed = 'appointment_confirmed';
    case AppointmentRescheduled = 'appointment_rescheduled';
    case AppointmentDeclined = 'appointment_declined';
    case AppointmentCancelled = 'appointment_cancelled';
    case AppointmentReminder = 'appointment_reminder';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->ucfirst()->toString();
    }

    /** Messages whose content depends on the appointment's current schedule version. */
    public function isScheduleBound(): bool
    {
        return in_array($this, [self::AppointmentConfirmed, self::AppointmentRescheduled, self::AppointmentReminder], true);
    }
}
