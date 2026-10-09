@php($tz = config('brivia.appointments.staff_timezone'))
<x-layouts.admin :title="'Enquiry '.$enquiry->public_reference">
    <x-admin.page-header :title="$enquiry->name" :description="'Enquiry '.$enquiry->public_reference.' · received '.$enquiry->created_at->timezone($tz)->format('j M Y, H:i').' ('.$tz.')'">
        <x-slot:actions><a href="{{ route('admin.enquiries.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> All enquiries</a></x-slot:actions>
    </x-admin.page-header>
    <x-form.error-summary />

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 space-y-6">
            <section class="card" aria-labelledby="msg-heading">
                <h2 id="msg-heading" class="h-card">Message</h2>
                <dl class="mt-3 grid gap-x-6 gap-y-2 text-[0.9375rem] sm:grid-cols-2">
                    <div><dt class="text-meta text-muted">Type</dt><dd>{{ $enquiry->enquiry_type->label() }}</dd></div>
                    @foreach (($enquiry->context_snapshot ?? []) as $label => $title)
                        <div><dt class="text-meta text-muted">{{ ucfirst($label) }}</dt><dd>{{ $title }}</dd></div>
                    @endforeach
                    @if ($enquiry->budget)<div><dt class="text-meta text-muted">Budget range</dt><dd>{{ $enquiry->budget }}</dd></div>@endif
                    @if ($enquiry->timeline)<div><dt class="text-meta text-muted">Timeline</dt><dd>{{ $enquiry->timeline }}</dd></div>@endif
                </dl>
                <div class="mt-4 whitespace-pre-line break-words rounded-[10px] bg-canvas p-4">{{ $enquiry->message }}</div>
            </section>
            <x-admin.notes :notes="$enquiry->notes" :action="route('admin.enquiries.notes', $enquiry)" />
            <section class="card" aria-labelledby="history-heading">
                <h2 id="history-heading" class="h-card">History</h2>
                <ol class="mt-3 space-y-2 text-[0.9375rem]">
                    <li><span class="text-muted">{{ $enquiry->created_at->timezone($tz)->format('j M Y H:i') }}</span> — received</li>
                    @foreach ($enquiry->events as $event)
                        <li><span class="text-muted">{{ $event->created_at->timezone($tz)->format('j M Y H:i') }}</span> — {{ $event->actor?->name ?? 'System' }}:
                            @if ($event->event_type === 'status') status {{ \App\Enums\EnquiryStatus::tryFrom($event->from_value)?->label() }} → {{ \App\Enums\EnquiryStatus::tryFrom($event->to_value)?->label() }}
                            @else assignment changed @endif
                        </li>
                    @endforeach
                </ol>
            </section>
            <x-admin.deliveries :deliveries="$enquiry->deliveries" />
        </div>

        <aside class="order-first space-y-4 lg:order-none" aria-label="Enquiry actions">
            <section class="card" aria-labelledby="contact-heading">
                <h2 id="contact-heading" class="h-card">Contact</h2>
                <dl class="mt-3 space-y-2 text-[0.9375rem]">
                    <div><dt class="text-meta text-muted">Email</dt><dd class="break-all"><a class="btn-link" href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: your enquiry '.$enquiry->public_reference) }}">{{ $enquiry->email }}</a></dd></div>
                    @if ($enquiry->phone)<div><dt class="text-meta text-muted">Phone</dt><dd>{{ $enquiry->phone }}</dd></div>@endif
                    @if ($enquiry->company)<div><dt class="text-meta text-muted">Company</dt><dd>{{ $enquiry->company }}</dd></div>@endif
                    <div><dt class="text-meta text-muted">Privacy notice accepted</dt><dd>{{ $enquiry->privacy_accepted_at->timezone($tz)->format('j M Y H:i') }} · version <code>{{ $enquiry->policy_version }}</code></dd></div>
                </dl>
                <button type="button" class="btn btn-secondary btn-sm mt-3" data-copy="{{ $enquiry->name }} <{{ $enquiry->email }}>">Copy contact</button>
            </section>

            <section class="card" aria-labelledby="status-heading">
                <h2 id="status-heading" class="h-card">Status</h2>
                <p class="mt-2"><span class="chip chip-blue">{{ $enquiry->status->label() }}</span></p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($enquiry->status->allowedTransitions() as $next)
                        <form method="POST" action="{{ route('admin.enquiries.status', $enquiry) }}">
                            @csrf
                            <input type="hidden" name="lock_version" value="{{ $enquiry->lock_version }}">
                            <input type="hidden" name="status" value="{{ $next->value }}">
                            <button type="submit" class="btn btn-secondary btn-sm">{{ $enquiry->status->isFinal() ? 'Reopen (in review)' : 'Mark '.strtolower($next->label()) }}</button>
                        </form>
                    @empty
                        <p class="text-muted">No further changes.</p>
                    @endforelse
                </div>
            </section>

            <section class="card" aria-labelledby="assign-heading">
                <h2 id="assign-heading" class="h-card">Assigned to</h2>
                <form method="POST" action="{{ route('admin.enquiries.assign', $enquiry) }}" class="mt-3 flex gap-2">
                    @csrf
                    <input type="hidden" name="lock_version" value="{{ $enquiry->lock_version }}">
                    <label for="assigned_to" class="sr-only">Assignee</label>
                    <select id="assigned_to" name="assigned_to" class="form-control">
                        <option value="">Unassigned</option>
                        @foreach ($staff as $person)<option value="{{ $person->id }}" @selected($enquiry->assigned_to === $person->id)>{{ $person->name }}</option>@endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary">Save</button>
                </form>
            </section>

            @can('delete-personal-records')
                @if ($enquiry->status->isFinal())
                    <section class="card" aria-labelledby="del-heading">
                        <h2 id="del-heading" class="h-card">Delete permanently</h2>
                        <p class="text-meta mt-1 text-muted">Removes the enquiry, notes, history and email records. This cannot be undone.</p>
                        <form method="POST" action="{{ route('admin.enquiries.destroy', $enquiry) }}" class="mt-3 space-y-3" data-confirm="Permanently delete this enquiry and all related records?">
                            @csrf @method('DELETE')
                            <x-form.field name="confirm_reference" :label="'Type '.$enquiry->public_reference.' to confirm'" required autocomplete="off" />
                            <button type="submit" class="btn btn-danger w-full">Delete enquiry</button>
                        </form>
                    </section>
                @endif
            @endcan
        </aside>
    </div>
</x-layouts.admin>
