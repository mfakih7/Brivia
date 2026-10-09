<?php

namespace App\Models\Concerns;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared publication state. Status/published_at are never mass assignable; use publish()/unpublish().
 */
trait HasPublication
{
    public function initializeHasPublication(): void
    {
        $this->mergeCasts([
            'status' => PublicationStatus::class,
            'published_at' => 'datetime',
        ]);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), PublicationStatus::Published->value)
            ->whereNotNull($this->qualifyColumn('published_at'))
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->qualifyColumn('id'));
    }

    public function isPublished(): bool
    {
        return $this->status === PublicationStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function markPublished(): void
    {
        $this->forceFill([
            'status' => PublicationStatus::Published,
            'published_at' => $this->published_at && $this->published_at->lte(now()) ? $this->published_at : now(),
        ])->save();
    }

    public function markUnpublished(): void
    {
        $this->forceFill(['status' => PublicationStatus::Draft])->save();
    }
}
