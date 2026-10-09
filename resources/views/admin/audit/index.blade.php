<x-layouts.admin title="Audit log">
    <x-admin.page-header title="Audit log" description="Read-only record of sensitive changes. Personal message content and secrets are never stored here." />
    <form method="GET" action="{{ route('admin.audit.index') }}" class="card mb-4 flex flex-wrap items-end gap-3 !p-4">
        <div><label for="a-type" class="form-label">Resource</label><select id="a-type" name="type" class="form-control"><option value="">All</option>@foreach ($types as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ str_replace('_', ' ', $t) }}</option>@endforeach</select></div>
        <div><label for="a-actor" class="form-label">Person</label><select id="a-actor" name="actor" class="form-control"><option value="">Anyone</option>@foreach ($actors as $a)<option value="{{ $a->id }}" @selected((int) request('actor') === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
        <div><label for="a-action" class="form-label">Action starts with</label><input id="a-action" name="action" value="{{ request('action') }}" maxlength="80" class="form-control" placeholder="e.g. content."></div>
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>
    @if ($logs->isEmpty())
        <x-admin.empty-state title="No audit entries match" />
    @else
        <x-admin.table label="Audit entries">
            <thead><tr><th scope="col">When (staff time)</th><th scope="col">Who</th><th scope="col">Action</th><th scope="col">Resource</th><th scope="col">Changes</th></tr></thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ $log->created_at->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y H:i:s') }}</td>
                        <td>{{ $log->actor?->name ?? 'System / visitor' }}</td>
                        <td><code class="text-sm">{{ $log->action }}</code></td>
                        <td>{{ str_replace('_', ' ', $log->resource_type) }} @if ($log->resource_id)#{{ $log->resource_id }}@endif</td>
                        <td class="text-meta">@foreach ($log->changed_fields ?? [] as $field => $value)<span class="mr-2 inline-block"><span class="text-muted">{{ $field }}:</span> {{ is_bool($value) ? ($value ? 'yes' : 'no') : (is_scalar($value) ? $value : json_encode($value)) }}</span>@endforeach</td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3">{{ $logs->links() }}</div>
    @endif
</x-layouts.admin>
