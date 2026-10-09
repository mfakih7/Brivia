<x-layouts.admin title="Email deliveries">
    <x-admin.page-header title="Email deliveries" description="Outcomes of automatic emails. Failed messages never change an enquiry or appointment; retry them here or contact the person directly." />
    <form method="GET" class="card mb-4 flex flex-wrap items-end gap-3 !p-4">
        <div>
            <label for="d-status" class="form-label">Status</label>
            <select id="d-status" name="status" class="form-control">
                <option value="">Failed and pending</option>
                @foreach (\App\Enums\DeliveryStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }} ({{ $counts[$s->value] ?? 0 }})</option>@endforeach
            </select>
        </div>
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>
    @if ($deliveries->isEmpty())
        <x-admin.empty-state title="Nothing needs attention" />
    @else
        <x-admin.table label="Email deliveries">
            <thead><tr><th scope="col">Created</th><th scope="col">Email</th><th scope="col">Record</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($deliveries as $d)
                    <tr>
                        <td class="whitespace-nowrap">{{ $d->created_at->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y H:i') }}</td>
                        <td>{{ $d->kind->label() }}<div class="text-meta text-muted break-all">{{ $d->recipient_email }}</div>@if ($d->last_error)<div class="text-meta text-danger">{{ $d->last_error }}</div>@endif</td>
                        <td>
                            @if ($d->enquiry)<a class="btn-link" href="{{ route('admin.enquiries.show', $d->enquiry_id) }}">{{ $d->enquiry->public_reference }}</a>@endif
                            @if ($d->appointment)<a class="btn-link" href="{{ route('admin.appointments.show', $d->appointment_id) }}">{{ $d->appointment->public_reference }}</a>@endif
                        </td>
                        <td><span class="chip">{{ $d->status->label() }}</span> <span class="text-meta text-muted">× {{ $d->attempts }}</span></td>
                        <td>@if ($d->status->value === 'failed')<form method="POST" action="{{ route('admin.notifications.retry', $d) }}">@csrf<button class="btn btn-secondary btn-sm" type="submit">Retry</button></form>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3">{{ $deliveries->links() }}</div>
    @endif
</x-layouts.admin>
