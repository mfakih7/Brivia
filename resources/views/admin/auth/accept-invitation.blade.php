<x-layouts.admin-auth title="Accept invitation">
    @if (! $invitation)
        <div class="alert alert-danger" role="alert">This invitation is invalid, expired, or has already been used. Ask an owner to send a new invitation.</div>
    @else
        <p class="mb-5 text-muted">You have been invited as <strong class="text-ink">{{ $invitation->role->label() }}</strong> for <strong class="text-ink">{{ $invitation->email }}</strong>. Choose your name and a strong password.</p>
        <form method="POST" action="{{ route('admin.invitations.accept', $token) }}" class="space-y-5" novalidate>
            @csrf
            <x-form.error-summary />
            <x-form.field name="name" label="Full name" required maxlength="120" autocomplete="name" />
            <x-form.field name="password" label="Password" type="password" required autocomplete="new-password" hint="At least 12 characters with upper and lower case letters, a number and a symbol." />
            <x-form.field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />
            <button type="submit" class="btn btn-primary w-full"><span data-label>Create account</span></button>
        </form>
    @endif
</x-layouts.admin-auth>
