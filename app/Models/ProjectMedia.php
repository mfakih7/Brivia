<?php

namespace App\Models;

use App\Enums\MediaRole;
use App\Models\Concerns\FlushesPublicCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['caption', 'sort_order'])]
class ProjectMedia extends Model
{
    use FlushesPublicCache;

    protected $table = 'project_media';

    protected function casts(): array
    {
        return ['role' => MediaRole::class];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
