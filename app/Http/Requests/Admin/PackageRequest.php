<?php

namespace App\Http\Requests\Admin;

use App\Enums\IconKey;
use App\Enums\PriceMode;
use Illuminate\Validation\Rule;

class PackageRequest extends ContentRequest
{
    protected array $stringLists = ['features', 'exclusions'];

    protected ?string $slugSource = 'name';

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Quote mode never stores an amount.
        if ($this->input('price_mode') === PriceMode::Quote->value) {
            $this->merge(['price_amount' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => $this->slugRules('packages', $this->route('package'), 180),
            'summary' => ['required', 'string', 'max:400'],
            'features' => ['array', 'max:20'],
            'features.*' => ['string', 'max:200'],
            'exclusions' => ['array', 'max:20'],
            'exclusions.*' => ['string', 'max:200'],
            'price_mode' => ['required', Rule::enum(PriceMode::class)],
            'price_amount' => ['nullable', 'required_unless:price_mode,quote', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'currency' => ['required', Rule::in(config('brivia.currencies'))],
            'billing_label' => ['nullable', 'string', 'max:60'],
            'icon_key' => ['required', Rule::enum(IconKey::class)],
            'is_featured' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'service_ids' => ['array', 'max:50'],
            'service_ids.*' => ['integer', 'distinct', 'exists:services,id'],
        ] + $this->seoRules();
    }

    public function messages(): array
    {
        return parent::messages() + ['price_amount.required_unless' => 'Enter an amount for fixed or starting-from pricing, or choose "Request a quote".'];
    }
}
