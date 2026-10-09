<?php

namespace App\Models;

use App\Enums\ConsultationPricingMode;
use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublication;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

#[Fillable(['name', 'slug', 'description', 'duration_minutes', 'pricing_mode', 'amount', 'currency', 'sort_order'])]
class ConsultationType extends Model
{
    use FlushesPublicCache, HasFactory, HasPublication;

    protected $attributes = ['pricing_mode' => 'free', 'currency' => 'USD'];

    protected function casts(): array
    {
        return [
            'pricing_mode' => ConsultationPricingMode::class,
            'amount' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function priceLabel(): string
    {
        return match ($this->pricing_mode) {
            ConsultationPricingMode::Free => 'Free',
            ConsultationPricingMode::Quote => 'Quoted on request',
            ConsultationPricingMode::Fixed => Number::currency((float) $this->amount, in: $this->currency),
        };
    }
}
