{{-- Compact contact option. Renders a link tile when $href is given, otherwise a static tile (e.g. location). --}}
@props(['icon', 'label', 'value', 'href' => null, 'external' => false, 'wide' => false])
@php
    $classes = 'group flex min-w-0 flex-col items-start gap-2.5 rounded-[12px] border border-line bg-surface p-3 transition sm:p-3.5 '
        .($wide ? 'col-span-2 ' : '')
        .($href ? 'hover:border-[#bcd0ea] hover:bg-canvas focus-visible:border-primary' : '');
@endphp
@if ($href)
    <a href="{{ $href }}" @if ($external) target="_blank" rel="noopener" @endif {{ $attributes->merge(['class' => $classes]) }}>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
@endif
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[9px] bg-[#ebf3ff] text-primary-strong" aria-hidden="true">
            <x-icon :name="$icon" size="17" />
        </span>
        <span class="min-w-0">
            <span class="block text-[0.75rem] font-semibold uppercase tracking-[0.05em] text-muted">{{ $label }}</span>
            <span class="mt-0.5 block text-[0.875rem] font-semibold leading-snug text-ink [overflow-wrap:anywhere] sm:text-[0.9375rem] {{ $href ? 'group-hover:text-primary-strong' : 'font-medium' }}">{{-- Escape first, then allow line breaks after @ and dots so emails/URLs wrap at natural points. --}}{!! preg_replace('/([@.])/', '$1<wbr>', e($value)) !!}@if ($external)<span class="sr-only"> (opens in a new tab)</span>@endif</span>
        </span>
@if ($href)
    </a>
@else
    </div>
@endif
