<?php

namespace App\Http\Requests\Admin;

use App\Enums\ConsultationPricingMode;
use Illuminate\Validation\Rule;

class ConsultationTypeRequest extends ContentRequest
{
    protected ?string $slugSource = 'name';

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->input('pricing_mode') !== ConsultationPricingMode::Fixed->value) {
            $this->merge(['amount' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => $this->slugRules('consultation_types', $this->route('consultationType'), 140),
            'description' => ['required', 'string', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'pricing_mode' => ['required', Rule::enum(ConsultationPricingMode::class)],
            'amount' => ['nullable', 'required_if:pricing_mode,fixed', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'currency' => ['required', Rule::in(config('brivia.currencies'))],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
