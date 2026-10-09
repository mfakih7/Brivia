<x-mail::message>
# You're invited to the {{ config('app.name') }} admin

You have been invited to join as **{{ $invitation->role->label() }}**.

Use the button below to choose your name and password. The link works once and expires {{ $invitation->expires_at->toDayDateTimeString() }} (UTC).

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

If you were not expecting this invitation, you can ignore this email.
</x-mail::message>
