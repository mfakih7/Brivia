@php($tz = config('brivia.appointments.staff_timezone'))
<x-layouts.admin title="Appointments">
    <x-admin.page-header title="Appointment requests" :description="'Requested times are preferences, not reservations. Times below are shown in staff time ('.$tz.').'" />
    <x-admin.list-toolbar :action="route('admin.appointments.index')" label="Search name, email, company or reference" :statuses="collect(\App\Enums\AppointmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()">
        <div>
            <label for="list-assignee" class="form-label">Assigned</label>
            <select id="list-assignee" name="assignee" class="form-control"><option value="">Anyone</option><option value="me" @selected(request('assignee') === 'me')>Me</option></select>
        </div>
    </x-admin.list-toolbar>

    @if ($appointments->isEmpty())
        <x-admin.empty-state title="No appointments match">Consultation requests from the website appear here.</x-admin.empty-state>
    @else
        <x-admin.table label="Appointments list">
            <thead><tr><th scope="col">Time</th><th scope="col">Client</th><th scope="col">Type</th><th scope="col">Assigned</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @foreach ($appointments as $appointment)
                    <tr>
                        <td class="whitespace-nowrap">
                            @if ($appointment->confirmed_start_at_utc)
                                <span class="font-semibold">{{ $appointment->confirmed_start_at_utc->setTimezone($tz)->format('D j M Y, H:i') }}</span><div class="text-meta text-muted">confirmed</div>
                            @else
                                {{ $appointment->preferred_at_utc->setTimezone($tz)->format('D j M Y, H:i') }}<div class="text-meta text-muted">preferred</div>
                            @endif
                        </td>
                        <td><a href="{{ route('admin.appointments.show', $appointment) }}" class="font-semibold hover:underline">{{ $appointment->name }}</a><div class="text-meta text-muted">{{ $appointment->public_reference }}</div></td>
                        <td>{{ $appointment->type_snapshot }}<div class="text-meta text-muted">{{ $appointment->duration_minutes }} min</div></td>
                        <td>{{ $appointment->assignee?->name ?? '—' }}</td>
                        <td><span class="chip {{ ['requested' => 'chip-blue', 'confirmed' => 'chip-green', 'cancelled' => 'chip-red', 'declined' => 'chip-red', 'completed' => ''][$appointment->status->value] }}">{{ $appointment->status->label() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3">{{ $appointments->links() }}</div>
    @endif
</x-layouts.admin>
