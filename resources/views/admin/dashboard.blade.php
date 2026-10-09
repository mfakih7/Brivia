<x-layouts.admin title="Dashboard">
    <x-admin.page-header title="Dashboard" description="Current counts from the database. No decorative metrics." />

    @if ($operations)
        @unless ($operations['staff_recipients_configured'])
            <div class="alert alert-warning mb-6" role="status">Staff notification recipients are not configured (<code>BRIVIA_STAFF_NOTIFICATION_EMAILS</code>). New submissions are saved but no staff alert email is queued.</div>
        @endunless
        <section aria-labelledby="ops-heading" class="mb-8">
            <h2 id="ops-heading" class="mb-3 font-display text-lg font-bold">Operations</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['New enquiries', $operations['new_enquiries'], route('admin.enquiries.index', ['status' => 'new'])],
                    ['Open enquiries', $operations['open_enquiries'], route('admin.enquiries.index')],
                    ['Appointment requests', $operations['requested_appointments'], route('admin.appointments.index', ['status' => 'requested'])],
                    ['Upcoming confirmed', $operations['upcoming_appointments'], route('admin.appointments.index', ['status' => 'confirmed'])],
                    ['Failed emails', $operations['failed_deliveries'], route('admin.notifications.index', ['status' => 'failed'])],
                ] as [$label, $count, $href])
                    <a href="{{ $href }}" class="card card-link block">
                        <p class="text-sm font-medium text-muted">{{ $label }}</p>
                        <p class="mt-1 font-display text-3xl font-extrabold {{ $label === 'Failed emails' && $count > 0 ? 'text-danger' : '' }}">{{ $count }}</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="card">
                    <h3 class="h-card">Recent enquiries</h3>
                    @forelse ($operations['recent_enquiries'] as $enquiry)
                        <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="mt-3 flex items-center justify-between gap-3 border-t border-border pt-3 first-of-type:border-0">
                            <span class="min-w-0"><span class="block truncate font-semibold">{{ $enquiry->name }}</span><span class="text-meta text-muted">{{ $enquiry->public_reference }} · {{ $enquiry->enquiry_type->label() }}</span></span>
                            <span class="chip">{{ $enquiry->status->label() }}</span>
                        </a>
                    @empty
                        <p class="mt-3 text-muted">No enquiries yet.</p>
                    @endforelse
                </div>
                <div class="card">
                    <h3 class="h-card">Recent appointment requests</h3>
                    @forelse ($operations['recent_appointments'] as $appointment)
                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="mt-3 flex items-center justify-between gap-3 border-t border-border pt-3 first-of-type:border-0">
                            <span class="min-w-0"><span class="block truncate font-semibold">{{ $appointment->name }}</span><span class="text-meta text-muted">{{ $appointment->public_reference }} · {{ $appointment->type_snapshot }}</span></span>
                            <span class="chip">{{ $appointment->status->label() }}</span>
                        </a>
                    @empty
                        <p class="mt-3 text-muted">No appointment requests yet.</p>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    @if ($content)
        <section aria-labelledby="content-heading">
            <h2 id="content-heading" class="mb-3 font-display text-lg font-bold">Content</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($content as $label => $counts)
                    <div class="card">
                        <p class="text-sm font-medium text-muted">{{ $label }}</p>
                        <p class="mt-1"><span class="font-display text-2xl font-extrabold">{{ $counts['published'] }}</span> <span class="text-muted">published</span></p>
                        <p class="text-meta text-muted">{{ $counts['draft'] }} draft</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($staffCount !== null)
        <p class="mt-8 text-sm text-muted">Active staff accounts: <strong class="text-ink">{{ $staffCount }}</strong></p>
    @endif
</x-layouts.admin>
