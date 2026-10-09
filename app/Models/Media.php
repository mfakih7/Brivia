<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * An approved public image. Paths are always generated server-side.
 * `variants` maps width => relative path of a re-encoded WebP derivative.
 */
#[Fillable(['alt_text'])]
class Media extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['variants' => 'array'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(?int $preferredWidth = null): string
    {
        $path = $this->storage_path;

        if ($preferredWidth && $this->variants) {
            $widths = collect(array_keys($this->variants))->map(fn ($w) => (int) $w)->sort()->values();
            $chosen = $widths->first(fn ($w) => $w >= $preferredWidth) ?? $widths->last();
            $path = $this->variants[$chosen] ?? $this->variants[(string) $chosen] ?? $path;
        }

        return Storage::disk($this->disk)->url($path);
    }

    public function srcset(): string
    {
        return collect($this->variants ?? [])
            ->map(fn ($path, $width) => Storage::disk($this->disk)->url($path).' '.$width.'w')
            ->implode(', ');
    }

    /** @return list<string> */
    public function allPaths(): array
    {
        return array_values(array_unique(array_merge([$this->storage_path], array_values($this->variants ?? []))));
    }

    /** True when any content record still points at this media. */
    public function isReferenced(): bool
    {
        return ProjectMedia::where('media_id', $this->id)->exists()
            || TeamMember::where('portrait_media_id', $this->id)->exists()
            || Service::where('og_media_id', $this->id)->exists()
            || Package::where('og_media_id', $this->id)->exists()
            || Project::where('og_media_id', $this->id)->exists();
    }
}
