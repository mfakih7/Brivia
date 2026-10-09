<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Transactional email outbox row. */
class NotificationDelivery extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'kind' => NotificationKind::class,
            'status' => DeliveryStatus::class,
            'payload' => 'array',
            'attempts' => 'integer',
            'scheduled_for' => 'datetime',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
