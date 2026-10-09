<x-layouts.public :title="$service->meta_title ?: $service->title" :description="$service->meta_description ?: $service->summary" :preview="$preview ?? false">
    <x-public.page-header eyebrow="Service" :title="$service->title" :intro="$service->summary" :crumbs="['Home' => route('home'), 'Services' => route('services.index')]">
        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('contact', ['service' => $service->slug]) }}" class="btn btn-primary btn-lg">Discuss this service <x-icon name="arrow-right" size="18" /></a>
            <a href="{{ route('consultation') }}" class="btn btn-secondary btn-lg">Book a consultation</a>
        </div>
    </x-public.page-header>

    <section class="section">
        <div class="container-site grid gap-14 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-7">
                @if ($service->body)
                    <p class="eyebrow">Who it helps</p>
                    <div class="prose-brivia mt-6 text-[1.0625rem] leading-[1.75]">{{ \App\Support\StructuredText::toHtml($service->body) }}</div>
                @endif
                @if ($service->engagement_steps)
                    <h2 class="h-section mt-16">How the engagement works</h2>
                    <ol class="mt-8 space-y-0">
                        @foreach ($service->engagement_steps as $i => $step)
                            <li class="relative flex gap-6 pb-8 last:pb-0">
                                @unless ($loop->last)<span class="absolute left-[1.0625rem] top-10 bottom-0 w-px bg-line" aria-hidden="true"></span>@endunless
                                <span class="relative flex h-[2.125rem] w-[2.125rem] shrink-0 items-center justify-center rounded-full border border-primary/40 bg-[#ebf3ff] font-display text-sm font-bold text-primary-strong" aria-hidden="true">{{ $i + 1 }}</span>
                                <span class="pt-1 text-[1.0625rem]"><span class="sr-only">Step {{ $i + 1 }}: </span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
            @if ($service->deliverables)
                <aside class="lg:col-span-4 lg:col-start-9" aria-labelledby="deliverables-title">
                    <div class="rounded-[18px] border border-line bg-canvas p-6 md:p-8 lg:sticky lg:top-28">
                        <h2 id="deliverables-title" class="h-card">Typical deliverables</h2>
                        <ul class="check-list mt-5 space-y-3">
                            @foreach ($service->deliverables as $item)
                                <li><x-icon name="check" size="16" />{{ $item }}</li>
                            @endforeach
                        </ul>
                        <p class="text-meta mt-6 border-t border-line pt-5 text-muted">These are examples, not commitments. Deliverables, timeline and price are confirmed in a written proposal.</p>
                        <a href="{{ route('contact', ['service' => $service->slug]) }}" class="btn btn-primary mt-6 w-full">Discuss this service <x-icon name="arrow-right" size="16" /></a>
                    </div>
                </aside>
            @endif
        </div>
    </section>

    @if ($packages->isNotEmpty())
        <section class="section bg-canvas" aria-labelledby="related-packages">
            <div class="container-site">
                <x-public.section-heading eyebrow="Packages" title="Related starting points" id="related-packages" />
                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($packages as $package)<x-public.package-card :package="$package" />@endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($projects->isNotEmpty())
        <section class="section" aria-labelledby="related-work">
            <div class="container-site">
                <x-public.section-heading eyebrow="Related work" title="Examples of this service" id="related-work" />
                <div class="mt-12 grid gap-x-8 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)<x-public.project-card :project="$project" />@endforeach
                </div>
            </div>
        </section>
    @endif

    <x-public.cta-strip heading="Let's talk about your project" body="Tell us what you're planning. We'll review it and get back to you." />
</x-layouts.public>
