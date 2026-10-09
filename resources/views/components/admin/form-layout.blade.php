@props(['action', 'method' => 'POST', 'model' => null, 'submit' => 'Save'])
<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
    <div class="min-w-0 space-y-6">
        <form method="POST" action="{{ $action }}" class="space-y-6" novalidate>
            @csrf
            @if (strtoupper($method) !== 'POST') @method($method) @endif
            @if ($model?->exists)<input type="hidden" name="loaded_version" value="{{ $model->updated_at?->getTimestamp() }}">@endif
            <x-form.error-summary />
            {{ $slot }}
            <div class="sticky bottom-0 z-10 -mx-1 flex flex-wrap items-center gap-3 border-t border-border bg-canvas/95 px-1 py-3 backdrop-blur">
                <button type="submit" class="btn btn-primary" data-loading-text="Saving…"><span data-label>{{ $submit }}</span></button>
                <span class="text-meta text-muted">Changes are kept if validation fails.</span>
            </div>
        </form>
        {{ $after ?? '' }}
    </div>
    @isset($aside)
        <aside class="space-y-4 lg:sticky lg:top-6">{{ $aside }}</aside>
    @endisset
</div>
