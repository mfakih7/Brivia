<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Appointment request. Times are stored as UTC instants plus the visitor's IANA timezone. */
class Appointment extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'preferred_at_utc' => 'immutable_datetime',
            'alternate_at_utc' => 'immutable_datetime',
            'confirmed_start_at_utc' => 'immutable_datetime',
            'confirmed_end_at_utc' => 'immutable_datetime',
            'privacy_accepted_at' => 'datetime',
            'duration_minutes' => 'integer',
            'schedule_version' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    public function consultationType(): BelongsTo
    {
        return $this->belongsTo(ConsultationType::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(RecordNote::class)->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AppointmentEvent::class)->orderBy('id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class)->orderBy('id');
    }
}
