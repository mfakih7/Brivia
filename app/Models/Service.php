<?php

namespace App\Models;

use App\Enums\IconKey;
use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'summary', 'body', 'deliverables', 'engagement_steps', 'icon_key', 'sort_order', 'meta_title', 'meta_description'])]
class Service extends Model
{
    use FlushesPublicCache, HasFactory, HasPublication;

    protected $attributes = ['deliverables' => '[]', 'engagement_steps' => '[]', 'icon_key' => 'lightbulb'];

    protected function casts(): array
    {
        return [
            'deliverables' => 'array',
            'engagement_steps' => 'array',
            'icon_key' => IconKey::class,
        ];
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function ogMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }
}
