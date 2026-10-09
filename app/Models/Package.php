<?php

namespace App\Models;

use App\Enums\IconKey;
use App\Enums\PriceMode;
use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

#[Fillable(['name', 'slug', 'summary', 'features', 'exclusions', 'price_mode', 'price_amount', 'currency', 'billing_label', 'icon_key', 'is_featured', 'sort_order', 'meta_title', 'meta_description'])]
class Package extends Model
{
    use FlushesPublicCache, HasFactory, HasPublication;

    protected $attributes = ['features' => '[]', 'exclusions' => '[]', 'price_mode' => 'quote', 'currency' => 'USD', 'icon_key' => 'monitor'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'exclusions' => 'array',
            'price_mode' => PriceMode::class,
            'price_amount' => 'decimal:2',
            'icon_key' => IconKey::class,
            'is_featured' => 'boolean',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function ogMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }

    /** Human price label; quote mode never shows an amount. */
    public function priceLabel(): string
    {
        if ($this->price_mode === PriceMode::Quote || $this->price_amount === null) {
            return 'Request a quote';
        }

        $amount = Number::currency((float) $this->price_amount, in: $this->currency, precision: fmod((float) $this->price_amount, 1.0) == 0.0 ? 0 : 2);
        $label = $this->price_mode === PriceMode::StartingFrom ? 'From '.$amount : $amount;

        return trim($label.' '.($this->billing_label ?? ''));
    }
}
