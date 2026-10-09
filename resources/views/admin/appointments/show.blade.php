@php
    $tz = $staffTimezone;
    $a = $appointment;
    $visitorTz = $a->requested_timezone;
    $both = fn ($utc) => $utc ? \App\Support\LocalTime::format($utc, $visitorTz).($visitorTz !== $tz ? ' — staff time: '.\App\Support\LocalTime::format($utc, $tz, false) : '') : '—';
    $scheduling = in_array($a->status->value, ['requested', 'confirmed'], true);
    $defaultStart = ($a->confirmed_start_at_utc ?? $a->preferred_at_utc)->setTimezone(old('timezone', $tz));
@endphp
<x-layouts.admin :title="'Appointment '.$a->public_reference">
    <x-admin.page-header :title="$a->name" :description="$a->type_snapshot.' · '.$a->public_reference">
        <x-slot:actions><a href="{{ route('admin.appointments.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> All appointments</a></x-slot:actions>
    </x-admin.page-header>
    <x-form.error-summary />

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="min-w-0 space-y-6">
            <section class="card" aria-labelledby="times-heading">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="times-heading" class="h-card">Times</h2>
                    <span class="chip {{ ['requested' => 'chip-blue', 'confirmed' => 'chip-green', 'cancelled' => 'chip-red', 'declined' => 'chip-red', 'completed' => ''][$a->status->value] }}">{{ $a->status->label() }}</span>
                </div>
                <dl class="mt-3 space-y-3 text-[0.9375rem]">
                    <div><dt class="text-meta text-muted">Visitor timezone</dt><dd>{{ $visitorTz }}</dd></div>
                    <div><dt class="text-meta text-muted">Preferred (request, not reserved)</dt><dd>{{ $both($a->preferred_at_utc) }}</dd></div>
                    @if ($a->alternate_at_utc)<div><dt class="text-meta text-muted">Alternative</dt><dd>{{ $both($a->alternate_at_utc) }}</dd></div>@endif
                    @if ($a->confirmed_start_at_utc)
                        <div><dt class="text-meta text-muted">Confirmed</dt><dd class="font-semibold">{{ $both($a->confirmed_start_at_utc) }} · {{ $a->confirmed_start_at_utc->diffInMinutes($a->confirmed_end_at_utc) }} min</dd></div>
                        <div><dt class="text-meta text-muted">Meeting link</dt><dd class="break-all">{{ $a->meeting_url ?: 'None yet — the client was told details will follow.' }}</dd></div>
                    @endif
                    @if ($a->cancellation_reason)<div><dt class="text-meta text-muted">Reason given</dt><dd>{{ $a->cancellation_reason }}</dd></div>@endif
                </dl>
            </section>

            <section class="card" aria-labelledby="summary-heading">
                <h2 id="summary-heading" class="h-card">What they want to discuss</h2>
                <div class="mt-3 whitespace-pre-line break-words rounded-[10px] bg-canvas p-4">{{ $a->summary }}</div>
            </section>

            @if ($scheduling)
                <section class="card" aria-labelledby="schedule-heading" id="schedule">
                    <h2 id="schedule-heading" class="h-card">{{ $a->status->value === 'confirmed' ? 'Reschedule' : 'Confirm an exact time' }}</h2>
                    <p class="text-meta mt-1 text-muted">The system checks the assignee's other confirmed appointments for overlaps. Back-to-back slots are allowed. Times are converted using the timezone database (daylight saving aware).</p>
                    <form method="POST" action="{{ route('admin.appointments.confirm', $a) }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="lock_version" value="{{ $a->lock_version }}">
                        <x-form.select name="assigned_to" label="Assigned staff member" :options="$staff->pluck('name', 'id')->all()" :value="$a->assigned_to ?? auth()->id()" required />
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-form.field name="start_date" label="Date" type="date" :value="$defaultStart->format('Y-m-d')" required />
                            <x-form.field name="start_time" label="Start time" type="time" :value="$defaultStart->format('H:i')" required step="300" />
                            <x-form.field name="duration_minutes" label="Duration (min)" type="number" :value="$a->confirmed_start_at_utc ? $a->confirmed_start_at_utc->diffInMinutes($a->confirmed_end_at_utc) : $a->duration_minutes" min="15" max="480" required />
                        </div>
                        <x-form.select name="timezone" label="These times are in" :options="$timezones" :value="$tz" required :hint="'Default is staff time ('.$tz.'). The client sees the time in '.$visitorTz.'.'" />
                        <x-form.field name="meeting_url" label="Meeting link" type="url" :value="$a->meeting_url" maxlength="2048" hint="Optional. Paste a real link you created; none is generated automatically." />
                        @if ($a->status->value === 'confirmed')
                            <x-form.textarea name="reason" label="Reason for rescheduling (sent to the client)" rows="2" maxlength="500" required />
                        @endif
                        <button type="submit" class="btn btn-primary">{{ $a->status->value === 'confirmed' ? 'Reschedule and email client' : 'Confirm and email client' }}</button>
                    </form>
                </section>
            @endif

            <x-admin.notes :notes="$a->notes" :action="route('admin.appointments.notes', $a)" />

            <section class="card" aria-labelledby="history-heading">
                <h2 id="history-heading" class="h-card">History</h2>
                <ol class="mt-3 space-y-2 text-[0.9375rem]">
                    <li><span class="text-muted">{{ $a->created_at->timezone($tz)->format('j M Y H:i') }}</span> — requested</li>
                    @foreach ($a->events as $event)
                        <li><span class="text-muted">{{ $event->created_at->timezone($tz)->format('j M Y H:i') }}</span> — {{ $event->actor?->name ?? 'System' }}: {{ $event->event_type }}
                            @if (! empty($event->new['start_utc'])) ({{ \App\Support\LocalTime::format(\Carbon\CarbonImmutable::parse($event->new['start_utc']), $tz, false) }})@endif
                            @if ($event->reason) — “{{ $event->reason }}”@endif
                        </li>
                    @endforeach
                </ol>
            </section>

            <x-admin.deliveries :deliveries="$a->deliveries" />
        </div>

        <aside class="space-y-4">
            <section class="card" aria-labelledby="contact-heading">
                <h2 id="contact-heading" class="h-card">Contact</h2>
                <dl class="mt-3 space-y-2 text-[0.9375rem]">
                    <div><dt class="text-meta text-muted">Email</dt><dd class="break-all"><a class="btn-link" href="mailto:{{ $a->email }}?subject={{ rawurlencode('Your consultation request '.$a->public_reference) }}">{{ $a->email }}</a></dd></div>
                    @if ($a->phone)<div><dt class="text-meta text-muted">Phone</dt><dd>{{ $a->phone }}</dd></div>@endif
                    @if ($a->company)<div><dt class="text-meta text-muted">Company</dt><dd>{{ $a->company }}</dd></div>@endif
                    <div><dt class="text-meta text-muted">Privacy notice accepted</dt><dd>{{ $a->privacy_accepted_at->timezone($tz)->format('j M Y H:i') }} · version <code>{{ $a->policy_version }}</code></dd></div>
                </dl>
            </section>

            @if ($a->status->value === 'requested')
                <section class="card" aria-labelledby="assign-heading">
                    <h2 id="assign-heading" class="h-card">Assigned to</h2>
                    <form method="POST" action="{{ route('admin.appointments.assign', $a) }}" class="mt-3 flex gap-2">
                        @csrf
                        <input type="hidden" name="lock_version" value="{{ $a->lock_version }}">
                        <label for="assign-select" class="sr-only">Assignee</label>
                        <select id="assign-select" name="assigned_to" class="form-control">
                            <option value="">Unassigned</option>
                            @foreach ($staff as $person)<option value="{{ $person->id }}" @selected($a->assigned_to === $person->id)>{{ $person->name }}</option>@endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary">Save</button>
                    </form>
                </section>
            @else
                <section class="card"><h2 class="h-card">Assigned to</h2><p class="mt-2">{{ $a->assignee?->name ?? '—' }}</p></section>
            @endif

            @if ($a->status->value === 'confirmed' && $a->confirmed_start_at_utc->isPast())
                <form method="POST" action="{{ route('admin.appointments.complete', $a) }}" class="card">
                    @csrf
                    <input type="hidden" name="lock_version" value="{{ $a->lock_version }}">
                    <h2 class="h-card">After the meeting</h2>
                    <button type="submit" class="btn btn-secondary mt-3 w-full">Mark as completed</button>
                </form>
            @endif

            @if ($scheduling)
                <section class="card" aria-labelledby="close-heading">
                    <h2 id="close-heading" class="h-card">{{ $a->status->value === 'requested' ? 'Decline or cancel' : 'Cancel' }}</h2>
                    <p class="text-meta mt-1 text-muted">The reason is included in the email to the client. Pending reminders are stopped.</p>
                    @foreach ($a->status->value === 'requested' ? ['decline' => 'Decline request', 'cancel' => 'Cancel request'] : ['cancel' => 'Cancel appointment'] as $action => $label)
                        <form method="POST" action="{{ route('admin.appointments.'.$action, $a) }}" class="mt-3 space-y-2" data-confirm="{{ $label }} and email the client?">
                            @csrf
                            <input type="hidden" name="lock_version" value="{{ $a->lock_version }}">
                            <x-form.textarea name="reason" :id="'reason-'.$action" :label="'Reason ('.$action.')'" rows="2" maxlength="500" required />
                            <button type="submit" class="btn btn-danger w-full">{{ $label }}</button>
                        </form>
                    @endforeach
                </section>
            @endif

            @can('delete-personal-records')
                @if ($a->status->isClosed())
                    <section class="card" aria-labelledby="del-heading">
                        <h2 id="del-heading" class="h-card">Delete permanently</h2>
                        <form method="POST" action="{{ route('admin.appointments.destroy', $a) }}" class="mt-3 space-y-3" data-confirm="Permanently delete this appointment and all related records?">
                            @csrf @method('DELETE')
                            <x-form.field name="confirm_reference" :label="'Type '.$a->public_reference.' to confirm'" required autocomplete="off" />
                            <button type="submit" class="btn btn-danger w-full">Delete appointment</button>
                        </form>
                    </section>
                @endif
            @endcan
        </aside>
    </div>
</x-layouts.admin>
