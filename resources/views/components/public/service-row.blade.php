@props(['service', 'index' => 1, 'detailed' => false])
<li class="group relative border-t border-line">
    <div class="grid gap-4 py-8 md:grid-cols-12 md:gap-8 md:py-10">
        <span class="index-num md:col-span-1 md:pt-1.5" aria-hidden="true">{{ str_pad($index, 2, '0', STR_PAD_LEFT) }}</span>
        <div class="md:col-span-4">
            <h3 class="font-display text-[1.375rem] font-bold leading-snug tracking-[-0.02em] md:text-[1.625rem]">
                <a href="{{ route('services.show', $service->slug) }}" class="after:absolute after:inset-0 after:content-[''] group-hover:text-primary-strong">{{ $service->title }}</a>
            </h3>
        </div>
        <div class="md:col-span-6">
            <p class="text-muted">{{ $service->summary }}</p>
            @if ($detailed && $service->deliverables)
                <ul class="check-list mt-5 grid gap-x-6 gap-y-2.5 text-[0.9375rem] sm:grid-cols-2">
                    @foreach (array_slice($service->deliverables, 0, 6) as $item)
                        <li><x-icon name="check" size="16" />{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <span class="hidden h-11 w-11 items-center justify-center self-start rounded-full border border-line text-ink transition group-hover:border-primary group-hover:bg-primary group-hover:text-white md:col-span-1 md:inline-flex md:justify-self-end" aria-hidden="true">
            <x-icon name="arrow-right" size="18" />
        </span>
    </div>
</li>
