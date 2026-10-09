<x-layouts.admin-auth title="Choose a new password">
    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.error-summary />
        <x-form.field name="email" label="Email" type="email" :value="$email" required autocomplete="username" />
        <x-form.field name="password" label="New password" type="password" required autocomplete="new-password" hint="At least 12 characters with upper and lower case letters, a number and a symbol." />
        <x-form.field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary w-full" data-loading-text="Saving…"><span data-label>Reset password</span></button>
    </form>
</x-layouts.admin-auth>
