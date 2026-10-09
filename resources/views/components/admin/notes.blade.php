@props(['notes', 'action'])
<section class="card" aria-labelledby="notes-heading">
    <h2 id="notes-heading" class="h-card">Internal notes</h2>
    <p class="text-meta mt-1 text-muted">Private to staff. Notes never trigger an email.</p>
    <form method="POST" action="{{ $action }}" class="mt-4 space-y-3">
        @csrf
        <x-form.textarea name="body" label="Add a note" rows="3" maxlength="5000" required />
        <button type="submit" class="btn btn-secondary">Add note</button>
    </form>
    <ul class="mt-4 space-y-3">
        @foreach ($notes as $note)
            <li class="rounded-[10px] bg-canvas p-3">
                <p class="text-meta text-muted">{{ $note->author->name }} · {{ $note->created_at->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y H:i') }}</p>
                <p class="mt-1 whitespace-pre-line break-words">{{ $note->body }}</p>
            </li>
        @endforeach
    </ul>
</section>
