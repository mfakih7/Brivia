<x-layouts.public title="About us" :description="$profile->public_intro">
    <x-public.page-header eyebrow="About us" title="Built by experience. Driven by your vision" :intro="$profile->public_intro" />

    @if ($profile->story)
        <section class="section" aria-labelledby="story-title">
            <div class="container-site grid gap-8 lg:grid-cols-12 lg:gap-10">
                <div class="lg:col-span-4">
                    <p class="eyebrow">Our story</p>
                    <h2 id="story-title" class="h-section mt-4">Bridge + Via</h2>
                </div>
                <div class="prose-brivia lead lg:col-span-7 lg:col-start-6">{{ \App\Support\StructuredText::toHtml($profile->story) }}</div>
            </div>
        </section>
    @endif

    @if ($profile->mission)
        <section class="on-dark bg-navy-950 text-white" aria-labelledby="mission-title">
            <div class="container-site py-16 md:py-24">
                <p id="mission-title" class="eyebrow">Our mission</p>
                <p class="mt-6 max-w-4xl font-display text-[1.75rem] font-bold leading-[1.2] tracking-[-0.025em] md:text-[2.5rem]">{{ $profile->mission }}</p>
            </div>
        </section>
    @endif

    @if ($team->isNotEmpty())
        <section class="section" aria-labelledby="team-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="The founders" title="Two senior developers. Over 10 years of experience each" id="team-title" intro="We combine software delivery with hands-on project management, so you work directly with the people building your product." />
                <div class="mt-12 grid gap-6 md:grid-cols-2">
                    @foreach ($team as $member)<x-public.founder-card :member="$member" />@endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($profile->values)
        <section class="section bg-canvas" aria-labelledby="values-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="What we value" title="How we work with you" id="values-title" />
                <ol class="mt-12 grid gap-x-8 gap-y-10 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($profile->values as $value)
                        <li class="border-t border-ink/15 pt-6">
                            <span class="index-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="h-card mt-3">{{ $value['title'] }}</h3>
                            @if (! empty($value['body']))<p class="mt-2 text-muted">{{ $value['body'] }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    @if ($steps)
        <section class="section" aria-labelledby="process-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="Delivery approach" title="From discovery to support" id="process-title" />
                <div class="mt-14"><x-public.process-steps :steps="$steps" /></div>
            </div>
        </section>
    @endif

    <x-public.cta-strip />
</x-layouts.public>
