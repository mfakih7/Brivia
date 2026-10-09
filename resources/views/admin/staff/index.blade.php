<x-layouts.admin title="Staff">
    <x-admin.page-header title="Staff" description="Invite staff, change roles and deactivate access. There must always be at least one active owner." />
    <x-form.error-summary />

    <x-admin.table label="Staff accounts">
        <thead><tr><th scope="col">Person</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Last sign-in</th><th scope="col">Actions</th></tr></thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td><span class="font-semibold">{{ $user->name }}</span> @if ($user->is(auth()->user()))<span class="chip ml-1">You</span>@endif<div class="text-meta text-muted">{{ $user->email }}</div></td>
                    <td>
                        <form method="POST" action="{{ route('admin.staff.role', $user) }}" class="flex items-center gap-2">
                            @csrf @method('PUT')
                            <label class="sr-only" for="role-{{ $user->id }}">Role for {{ $user->name }}</label>
                            <select id="role-{{ $user->id }}" name="role" class="form-control !min-h-10 !py-1.5">
                                @foreach ($roles as $role)<option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>@endforeach
                            </select>
                            <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                        </form>
                    </td>
                    <td>
                        @if ($user->is_active)<span class="chip chip-green">Active</span>@else<span class="chip chip-red">Deactivated</span>@endif
                    </td>
                    <td class="text-meta text-muted whitespace-nowrap">{{ $user->last_login_at?->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y H:i') ?? 'Never' }}</td>
                    <td>
                        @unless ($user->is(auth()->user()))
                            @if ($user->is_active)
                                <form method="POST" action="{{ route('admin.staff.deactivate', $user) }}" data-confirm="Deactivate {{ $user->name }}? They will be signed out immediately.">@csrf<button type="submit" class="btn btn-danger btn-sm">Deactivate</button></form>
                            @else
                                <form method="POST" action="{{ route('admin.staff.reactivate', $user) }}">@csrf<button type="submit" class="btn btn-secondary btn-sm">Reactivate</button></form>
                            @endif
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="card" aria-labelledby="invite-heading">
            <h2 id="invite-heading" class="h-card">Invite staff</h2>
            <p class="text-meta mt-1 text-muted">The invitation email contains a single-use setup link valid for 48 hours. It never contains a password.</p>
            <form method="POST" action="{{ route('admin.staff.invite') }}" class="mt-4 space-y-4">
                @csrf
                <x-form.field name="email" label="Email" type="email" required maxlength="254" />
                <x-form.select name="role" label="Role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" value="content_editor" required />
                <button type="submit" class="btn btn-primary">Send invitation</button>
            </form>
        </section>
        <section class="card" aria-labelledby="inv-heading">
            <h2 id="inv-heading" class="h-card">Recent invitations</h2>
            <ul class="mt-3 divide-y divide-border">
                @forelse ($invitations as $invitation)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <span><span class="font-semibold">{{ $invitation->email }}</span><span class="text-meta block text-muted">{{ $invitation->role->label() }} · {{ $invitation->statusLabel() }} · by {{ $invitation->inviter->name }}</span></span>
                        @if ($invitation->isPending())
                            <form method="POST" action="{{ route('admin.staff.invitations.revoke', $invitation) }}">@csrf @method('DELETE')<button type="submit" class="btn btn-secondary btn-sm">Revoke</button></form>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-muted">No invitations yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
