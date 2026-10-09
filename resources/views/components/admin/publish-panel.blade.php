@props(['item', 'routePrefix', 'param', 'preview' => true, 'deletable' => true])
@php($problems = session('publication_problems', []))
<section class="card" aria-labelledby="publish-heading">
    <h2 id="publish-heading" class="h-card">Publication</h2>
    <div class="mt-3 flex flex-wrap gap-2"><x-admin.status-chip :item="$item" /></div>
    @if ($item->published_at)
        <p class="text-meta mt-2 text-muted">First published {{ $item->published_at->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y, H:i') }}</p>
    @endif
    <p class="text-meta mt-2 text-muted">Saving changes never changes publication. Use the buttons below.</p>

    @if ($problems)
        <div class="alert alert-danger mt-4" role="alert">
            <p class="font-semibold">Before publishing:</p>
            <ul class="mt-1 list-disc pl-5">@foreach ($problems as $problem)<li>{{ $problem }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mt-4 flex flex-col gap-2">
        @if ($item->isPublished() || $item->status?->value === 'published')
            <form method="POST" action="{{ route($routePrefix.'.unpublish', [$param => $item]) }}" data-confirm="Unpublish this item? It will disappear from the public website.">
                @csrf
                <button type="submit" class="btn btn-secondary w-full">Unpublish</button>
            </form>
        @else
            <form method="POST" action="{{ route($routePrefix.'.publish', [$param => $item]) }}">
                @csrf
                <button type="submit" class="btn btn-primary w-full">Publish</button>
            </form>
        @endif
        @if ($preview)
            <a href="{{ route($routePrefix.'.preview', [$param => $item]) }}" class="btn btn-secondary w-full" target="_blank" rel="noopener"><x-icon name="eye" size="18" /> Preview saved version</a>
        @endif
    </div>
    {{ $slot }}
</section>

@if ($deletable)
    <section class="card mt-4" aria-labelledby="delete-heading">
        <h2 id="delete-heading" class="h-card">Delete</h2>
        <p class="text-meta mt-1 text-muted">Items used elsewhere cannot be deleted; unpublish them instead.</p>
        <form method="POST" action="{{ route($routePrefix.'.destroy', [$param => $item]) }}" class="mt-3" data-confirm="Delete this item permanently? This cannot be undone.">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger w-full"><x-icon name="trash" size="18" /> Delete</button>
        </form>
    </section>
@endif
