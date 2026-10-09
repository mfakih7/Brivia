<x-layouts.admin-auth title="Reset password">
    <p class="mb-5 text-muted">Enter your staff email. If an active account exists, we will send a reset link valid for 30 minutes.</p>
    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-form.error-summary />
        <x-form.field name="email" label="Email" type="email" required autocomplete="username" maxlength="254" />
        <button type="submit" class="btn btn-primary w-full" data-loading-text="Sending…"><span data-label>Send reset link</span></button>
    </form>
    <p class="mt-5 text-sm"><a class="btn-link" href="{{ route('admin.login') }}"><x-icon name="arrow-left" size="16" /> Back to sign in</a></p>
</x-layouts.admin-auth>
