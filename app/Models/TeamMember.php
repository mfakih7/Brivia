<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'role_title', 'biography', 'skills', 'years_experience', 'social_links', 'sort_order'])]
class TeamMember extends Model
{
    use FlushesPublicCache, HasFactory, HasPublication;

    public const SOCIAL_NETWORKS = ['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'website' => 'Website'];

    protected $attributes = ['skills' => '[]', 'social_links' => '{}'];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'social_links' => 'array',
            'years_experience' => 'integer',
            'is_placeholder' => 'boolean',
        ];
    }

    public function portrait(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'portrait_media_id');
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }
}
