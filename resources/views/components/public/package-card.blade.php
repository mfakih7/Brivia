@props(['package', 'compact' => false])
@php($featured = $package->is_featured && ! $compact)
<article class="relative flex h-full flex-col rounded-[18px] border p-6 md:p-8 {{ $featured ? 'on-dark border-navy-900 bg-navy-950 text-white' : 'border-line bg-surface' }}" aria-labelledby="pkg-{{ $package->id }}">
    <div class="flex items-center justify-between gap-4">
        <span class="flex h-11 w-11 items-center justify-center rounded-full {{ $featured ? 'bg-white/10 text-cyan' : 'bg-[#ebf3ff] text-primary-strong' }}" aria-hidden="true"><x-icon :name="$package->icon_key->value" size="20" /></span>
    </div>
    <h3 id="pkg-{{ $package->id }}" class="mt-6 font-display text-[1.375rem] font-bold leading-snug tracking-[-0.02em]">{{ $package->name }}</h3>
    <p class="mt-1 font-display text-[1.0625rem] font-semibold {{ $featured ? 'text-cyan' : 'text-primary-strong' }}">{{ $package->priceLabel() }}</p>
    <p class="mt-4 {{ $featured ? 'text-on-dark-muted' : 'text-muted' }}">{{ $package->summary }}</p>

    @if ($package->features)
        <div class="mt-6 border-t pt-6 {{ $featured ? 'border-white/10' : 'border-line' }}">
            <h4 class="text-xs font-semibold uppercase tracking-[0.14em] {{ $featured ? 'text-on-dark-subtle' : 'text-muted' }}">Included</h4>
            <ul class="check-list mt-4 space-y-2.5 text-[0.9375rem]">
                @foreach ($compact ? array_slice($package->features, 0, 3) : $package->features as $feature)
                    <li><x-icon name="check" size="16" class="{{ $featured ? '!text-cyan' : '' }}" />{{ $feature }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (! $compact && $package->exclusions)
        <details class="mt-5 text-[0.9375rem]">
            <summary class="cursor-pointer font-semibold {{ $featured ? 'text-white' : 'text-ink' }}">Not included</summary>
            <ul class="mt-3 space-y-2">
                @foreach ($package->exclusions as $exclusion)
                    <li class="flex gap-2.5 {{ $featured ? 'text-on-dark-muted' : 'text-muted' }}"><x-icon name="x" size="16" class="mt-1 shrink-0" />{{ $exclusion }}</li>
                @endforeach
            </ul>
        </details>
    @endif
    <div class="mt-auto pt-8">
        <a href="{{ route('contact', ['package' => $package->slug]) }}" class="btn {{ $featured ? 'btn-primary' : 'btn-secondary' }} w-full">
            {{ $package->price_mode->value === 'quote' ? 'Request a quote' : 'Discuss this package' }} <x-icon name="arrow-right" size="16" />
            <span class="sr-only">for {{ $package->name }}</span>
        </a>
    </div>
</article>
