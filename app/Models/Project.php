<?php

namespace App\Models;

use App\Enums\MediaRole;
use App\Enums\WorkOrigin;
use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title', 'slug', 'category_id', 'summary', 'client_display_name', 'work_origin', 'contribution',
    'problem', 'approach', 'solution', 'outcomes', 'technologies', 'website_url', 'is_featured',
    'permission_confirmed', 'sort_order', 'meta_title', 'meta_description',
])]
class Project extends Model
{
    use FlushesPublicCache, HasFactory, HasPublication;

    protected $attributes = ['technologies' => '[]'];

    protected function casts(): array
    {
        return [
            'work_origin' => WorkOrigin::class,
            'technologies' => 'array',
            'is_featured' => 'boolean',
            'permission_confirmed' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function projectMedia(): HasMany
    {
        return $this->hasMany(ProjectMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function cover(): HasOne
    {
        return $this->hasOne(ProjectMedia::class)->where('role', MediaRole::Cover->value);
    }

    public function gallery(): HasMany
    {
        return $this->projectMedia()->where('role', MediaRole::Gallery->value);
    }

    public function ogMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /** Stable newest-first public ordering. */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }
}
