{{--
    Shared service card (homepage + Services page).
    - The whole card is clickable through the visible "Explore service" link (stretched), so there is
      exactly one link per card and the action never depends on hover.
    - "detailed" adds the deliverables list from 768px up; phones stay concise (full detail on the service page).
    - Narrow two-column phones: tighter padding and a slightly smaller title; long words hyphenate/wrap.
--}}
@props(['service', 'detailed' => false, 'headingLevel' => 'h3'])
<article {{ $attributes->merge(['class' => 'group relative flex h-full min-w-0 flex-col rounded-[16px] border border-line bg-surface p-3.5 shadow-[0_1px_2px_rgba(6,21,40,0.04)] transition duration-200 hover:-translate-y-0.5 hover:border-[#c5d3e4] hover:shadow-[0_16px_36px_-22px_rgba(11,36,66,0.4)] has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-2 has-[a:focus-visible]:outline-primary has-[a:focus-visible]:outline sm:p-5 md:p-7']) }}>
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] bg-gradient-to-br from-[#e9f2ff] to-[#e3fafd] text-primary-strong ring-1 ring-inset ring-[#d6e6ff] sm:h-12 sm:w-12" aria-hidden="true">
        <x-icon :name="$service->icon_key->value" size="22" />
    </span>

    <{{ $headingLevel }} lang="en" class="mt-4 font-display text-[0.9375rem] font-bold leading-snug tracking-[-0.01em] hyphens-auto sm:mt-5 sm:text-[1.125rem] md:text-[1.25rem]">{{ $service->title }}</{{ $headingLevel }}>

    <p class="mt-2 text-[0.875rem] leading-relaxed text-muted sm:text-[0.9375rem] {{ $detailed ? 'line-clamp-5 md:line-clamp-none' : '' }}">{{ $service->summary }}</p>

    @if ($detailed && $service->deliverables)
        <div class="mt-5 hidden border-t border-line pt-5 md:block">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Typical deliverables</p>
            <ul class="check-list mt-3 grid gap-x-5 gap-y-2 text-[0.9375rem] lg:grid-cols-2">
                @foreach (array_slice($service->deliverables, 0, 6) as $item)
                    <li><x-icon name="check" size="16" />{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <a href="{{ route('services.show', $service->slug) }}"
       class="mt-auto inline-block pt-4 text-[0.875rem] font-semibold text-primary-strong after:absolute after:inset-0 after:rounded-[16px] after:content-[''] focus-visible:outline-none sm:pt-6 sm:text-[0.9375rem]">
        Explore service<span class="sr-only">: {{ $service->title }}</span>
        <x-icon name="arrow-right" size="16" class="ml-1 inline-block align-[-3px] transition-transform duration-200 group-hover:translate-x-0.5" />
    </a>
</article>
