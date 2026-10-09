@props(['deliveries'])
<section class="card" aria-labelledby="deliveries-heading">
    <h2 id="deliveries-heading" class="h-card">Email deliveries</h2>
    <p class="text-meta mt-1 text-muted">Automatic acknowledgements and scheduling emails. A "sent" status means the mail provider accepted the message, not that it was read.</p>
    @if ($deliveries->isEmpty())
        <p class="mt-3 text-muted">No emails for this record.</p>
    @else
        <ul class="mt-3 divide-y divide-border">
            @foreach ($deliveries as $delivery)
                <li class="flex flex-wrap items-start justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $delivery->kind->label() }}</p>
                        <p class="text-meta text-muted">To {{ $delivery->recipient_email }} · attempts {{ $delivery->attempts }}
                            @if ($delivery->scheduled_for) · scheduled {{ $delivery->scheduled_for->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y H:i') }}@endif
                            @if ($delivery->sent_at) · sent {{ $delivery->sent_at->timezone(config('brivia.appointments.staff_timezone'))->format('j M Y H:i') }}@endif
                        </p>
                        @if ($delivery->last_error)<p class="text-meta mt-1 {{ $delivery->status->value === 'failed' ? 'text-danger' : 'text-muted' }}">{{ $delivery->last_error }}</p>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="chip {{ ['sent' => 'chip-green', 'failed' => 'chip-red', 'pending' => 'chip-amber', 'suppressed' => ''][$delivery->status->value] }}">{{ $delivery->status->label() }}</span>
                        @if ($delivery->status->value === 'failed')
                            <form method="POST" action="{{ route('admin.notifications.retry', $delivery) }}">@csrf<button type="submit" class="btn btn-secondary btn-sm">Retry</button></form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
