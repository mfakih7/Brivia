<?php

namespace App\Models;

use App\Enums\LegalPageKey;
use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'body', 'version_label'])]
class LegalPage extends Model
{
    use FlushesPublicCache, HasPublication;

    protected function casts(): array
    {
        return [
            'key' => LegalPageKey::class,
            'is_placeholder' => 'boolean',
        ];
    }

    public static function for(LegalPageKey $key): ?self
    {
        return static::where('key', $key->value)->first();
    }

    /** Version recorded with visitor consent; drafts are labelled so the record is honest. */
    public static function currentPrivacyVersion(): string
    {
        $page = static::for(LegalPageKey::Privacy);

        return match (true) {
            $page === null => 'unpublished',
            $page->isPublished() => $page->version_label,
            default => 'draft:'.$page->version_label,
        };
    }
}
