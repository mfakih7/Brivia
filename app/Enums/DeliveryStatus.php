<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    /** No longer relevant, e.g. a reminder for a cancelled or rescheduled appointment. */
    case Suppressed = 'suppressed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
