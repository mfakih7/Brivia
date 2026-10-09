<x-layouts.admin title="Account security">
    <x-admin.page-header title="Account security" description="Change your password. Changing it signs out your other sessions." />

    <section class="card max-w-xl" aria-labelledby="pw-heading">
        <h2 id="pw-heading" class="h-card">Change password</h2>
        <form method="POST" action="{{ route('admin.account.password') }}" class="mt-4 space-y-4" novalidate>
            @csrf @method('PUT')
            <x-form.error-summary />
            <x-form.field name="current_password" label="Current password" type="password" required autocomplete="current-password" />
            <x-form.field name="password" label="New password" type="password" required autocomplete="new-password" hint="At least 12 characters with upper and lower case letters, a number and a symbol." />
            <x-form.field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
            <button class="btn btn-primary" type="submit">Update password</button>
        </form>
    </section>
</x-layouts.admin>
