<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Internal staff note attached to exactly one enquiry or appointment. */
class RecordNote extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (self $note) {
            if (($note->enquiry_id === null) === ($note->appointment_id === null)) {
                throw new LogicException('A note must belong to exactly one enquiry or appointment.');
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
