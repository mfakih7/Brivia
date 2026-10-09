@props(['action', 'statuses' => ['draft' => 'Draft', 'published' => 'Published'], 'label' => 'Search'])
<form method="GET" action="{{ $action }}" class="card mb-4 flex flex-wrap items-end gap-3 !p-4" role="search">
    <div class="min-w-[12rem] flex-1">
        <label for="list-q" class="form-label">{{ $label }}</label>
        <input id="list-q" name="q" type="search" maxlength="100" value="{{ request('q') }}" class="form-control">
    </div>
    @if ($statuses)
        <div>
            <label for="list-status" class="form-label">Status</label>
            <select id="list-status" name="status" class="form-control">
                <option value="">All</option>
                @foreach ($statuses as $value => $text)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $text }}</option>
                @endforeach
            </select>
        </div>
    @endif
    {{ $slot }}
    <button type="submit" class="btn btn-secondary">Apply</button>
    @if (request()->query())
        <a href="{{ $action }}" class="btn-link self-center">Clear filters</a>
    @endif
</form>
