<x-layouts.public title="Book a consultation" description="Request a consultation time. Preferred times are confirmed manually by BRIVIA.">
    <x-public.page-header eyebrow="Consultation" title="Book a consultation" intro="Choose a consultation type and tell us when suits you. This is a request: your preferred time is not reserved until BRIVIA confirms it by email." />

    <section class="section">
        <div class="container-site grid gap-12 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-8">
                <div class="rounded-[20px] border border-line bg-surface p-6 md:p-10">
                    <h2 class="h-section !text-[1.75rem]">Request a time</h2>
                    <p class="mt-2 text-muted">Fields marked <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">with an asterisk</span> are required.</p>
                    @if (session('appointment_reference'))
                        <div class="alert alert-success mt-6" role="status" tabindex="-1" data-error-summary>
                            <p class="font-semibold">Your appointment request has been received. Your preferred time is not reserved until BRIVIA confirms it.</p>
                            <p class="mt-1 text-sm">Your reference: <strong>{{ session('appointment_reference') }}</strong></p>
                        </div>
                    @endif
                    @if ($types->isEmpty())
                        <div class="alert alert-info mt-6">Consultation booking is not available yet. Please <a class="underline" href="{{ route('contact') }}">send us a message</a> instead.</div>
                    @else
                        @if (! $formEnabled)
                            <div class="alert alert-warning mt-6" role="status">Online requests are not enabled in this review build yet.</div>
                        @endif
                        @include('public.partials.consultation-form')
                    @endif
                </div>
            </div>
            <aside class="space-y-6 lg:col-span-4">
                <div class="on-dark rounded-[20px] bg-navy-950 p-6 text-white md:p-8 lg:sticky lg:top-28">
                    <h2 class="h-card">How booking works</h2>
                    <ol class="mt-6 space-y-5">
                        @foreach (['We receive your request and preferred time.', 'We check availability and confirm the exact time by email — or suggest another.', 'Meeting details are sent with the confirmation or shortly after.'] as $step)
                            <li class="flex gap-4"><span class="index-num pt-0.5" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="text-on-dark-muted">{{ $step }}</span></li>
                        @endforeach
                    </ol>
                    @if ($types->isNotEmpty())
                        <div class="mt-7 border-t border-white/10 pt-6">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.14em] text-on-dark-subtle">Consultation types</h3>
                            <ul class="mt-4 space-y-4">
                                @foreach ($types as $type)
                                    <li><p class="font-semibold">{{ $type->name }}</p><p class="text-meta text-on-dark-subtle">{{ $type->duration_minutes }} minutes · {{ $type->priceLabel() }}</p><p class="text-meta mt-1 text-on-dark-muted">{{ $type->description }}</p></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
