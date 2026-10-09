<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Singleton (id = 1). */
#[Fillable(['public_intro', 'story', 'mission', 'values'])]
class CompanyProfile extends Model
{
    use FlushesPublicCache;

    protected $table = 'company_profile';

    protected $attributes = ['values' => '[]'];

    protected function casts(): array
    {
        return ['values' => 'array'];
    }

    public static function current(): self
    {
        return static::find(1) ?? tap(new static, fn (self $profile) => $profile->forceFill(['id' => 1])->save());
    }
}
