<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Private prospect record. Created only through SubmitEnquiry; no mass assignment. */
class Enquiry extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'enquiry_type' => EnquiryType::class,
            'status' => EnquiryStatus::class,
            'context_snapshot' => 'array',
            'privacy_accepted_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
        return $this->hasMany(EnquiryEvent::class)->orderBy('id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class)->orderBy('id');
    }
}
