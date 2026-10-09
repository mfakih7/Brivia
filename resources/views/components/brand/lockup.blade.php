@props(['href' => null, 'size' => 'md', 'tagline' => false])
@php
    $mark = $size === 'sm' ? 32 : 40;
    $text = $size === 'sm' ? 'text-lg' : 'text-xl';
@endphp
<a href="{{ $href ?? route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 text-white no-underline']) }}>
    <img src="{{ asset('images/brand/brivia-mark-96.png') }}" width="{{ $mark }}" height="{{ $mark }}" alt="" class="rounded-[10px]">
    <span class="flex flex-col leading-none">
        <span class="font-display font-extrabold tracking-[0.22em] {{ $text }}">BRIVIA</span>
        @if ($tagline)
            <span class="mt-1.5 text-[0.625rem] font-semibold tracking-[0.16em] text-on-dark-muted uppercase">The bridge from idea to product</span>
        @endif
    </span>
    <span class="sr-only">— home</span>
</a>
