@props(['eyebrow' => null, 'title', 'id' => null, 'intro' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col gap-6 md:flex-row md:items-end md:justify-between']) }}>
    <div class="max-w-2xl">
        @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
        <h2 @if ($id) id="{{ $id }}" @endif class="h-section mt-4">{{ rtrim($title, '.') }}</h2>
        @if ($intro)<p class="lead mt-4 text-muted">{{ $intro }}</p>@endif
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
