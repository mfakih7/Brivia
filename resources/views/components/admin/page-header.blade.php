@props(['title', 'description' => null])
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="font-display text-2xl font-extrabold sm:text-[1.75rem]">{{ $title }}</h1>
        @if ($description)<p class="mt-1 text-muted">{{ $description }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
