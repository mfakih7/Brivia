<x-layouts.admin title="Enquiries">
    <x-admin.page-header title="Enquiries" description="Messages from the contact form. Reply by email; nothing is sent automatically except the visitor's acknowledgement." />
    <x-admin.list-toolbar :action="route('admin.enquiries.index')" label="Search name, email, company or reference" :statuses="collect(\App\Enums\EnquiryStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()">
        <div>
            <label for="list-type" class="form-label">Type</label>
            <select id="list-type" name="type" class="form-control">
                <option value="">All</option>
                @foreach (\App\Enums\EnquiryType::cases() as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="list-assignee" class="form-label">Assigned</label>
            <select id="list-assignee" name="assignee" class="form-control"><option value="">Anyone</option><option value="me" @selected(request('assignee') === 'me')>Me</option></select>
        </div>
    </x-admin.list-toolbar>

    @if ($enquiries->isEmpty())
        <x-admin.empty-state title="No enquiries match">New enquiries from the contact form appear here.</x-admin.empty-state>
    @else
        <x-admin.table label="Enquiries list">
            <thead><tr><th scope="col">Received (staff time)</th><th scope="col">From</th><th scope="col">Type</th><th scope="col">Assigned</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @foreach ($enquiries as $enquiry)
                    <tr>
                        <td class="whitespace-nowrap">{{ $enquiry->created_at->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y H:i') }}<div class="text-meta text-muted">{{ $enquiry->public_reference }}</div></td>
                        <td><a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-semibold hover:underline">{{ $enquiry->name }}</a><div class="text-meta text-muted break-all">{{ $enquiry->email }}</div></td>
                        <td>{{ $enquiry->enquiry_type->label() }}</td>
                        <td>{{ $enquiry->assignee?->name ?? '—' }}</td>
                        <td><span class="chip {{ $enquiry->status->value === 'new' ? 'chip-blue' : '' }}">{{ $enquiry->status->label() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3">{{ $enquiries->links() }}</div>
    @endif
</x-layouts.admin>
