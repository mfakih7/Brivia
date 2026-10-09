<x-layouts.admin-auth title="Sign in">
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5" novalidate>
        @csrf
        <x-form.error-summary title="Sign-in failed" />
        <x-form.field name="email" label="Email" type="email" required autocomplete="username" maxlength="254" autofocus />
        <x-form.field name="password" label="Password" type="password" required autocomplete="current-password" />
        <button type="submit" class="btn btn-primary w-full" data-loading-text="Signing in…"><span data-label>Sign in</span></button>
    </form>
    <p class="mt-5 text-sm"><a class="btn-link" href="{{ route('admin.password.request') }}">Forgot your password?</a></p>
</x-layouts.admin-auth>
