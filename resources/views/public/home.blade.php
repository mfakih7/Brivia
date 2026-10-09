<x-layouts.public>
    {{-- Hero: typography-led, no illustration. --}}
    <section class="on-dark relative overflow-hidden bg-navy-950 text-white" aria-labelledby="hero-title">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(60rem_32rem_at_85%_-10%,rgba(0,107,255,0.26),transparent_65%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(36rem_20rem_at_0%_110%,rgba(0,203,234,0.10),transparent_70%)]"></div>
        </div>
        <div class="container-site relative pb-16 pt-14 md:pb-20 md:pt-20 lg:pb-28 lg:pt-28">
            @if ($home->hero_eyebrow)<p class="eyebrow">{{ $home->hero_eyebrow }}</p>@endif
            <h1 id="hero-title" class="h-hero mt-6 max-w-[16ch]"><span class="accent-dot">{{ rtrim($home->hero_headline, '.') }}</span></h1>

            <div class="mt-10 grid gap-12 lg:mt-12 lg:grid-cols-12 lg:items-end lg:gap-10">
                <div class="lg:col-span-7">
                    @if ($home->hero_subtitle)<p class="lead max-w-xl text-on-dark-muted">{{ $home->hero_subtitle }}</p>@endif
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route($home->primary_cta_route) }}" class="btn btn-primary btn-lg">{{ $home->primary_cta_label }} <x-icon name="arrow-right" size="18" /></a>
                        @if ($home->secondary_cta_label && $home->secondary_cta_route)
                            <a href="{{ route($home->secondary_cta_route) }}" class="btn btn-secondary btn-lg">{{ $home->secondary_cta_label }}</a>
                        @endif
                    </div>
                </div>
                @if ($home->credibility_items)
                    <ul class="border-t border-white/10 lg:col-span-4 lg:col-start-9" aria-label="Our strengths">
                        @foreach ($home->credibility_items as $item)
                            <li class="flex items-baseline gap-5 border-b border-white/10 py-4">
                                <span class="index-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="font-display text-[1.0625rem] font-semibold tracking-[-0.01em]">{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    @if ($home->showsSection('services') && $services->isNotEmpty())
        <section class="section" aria-labelledby="services-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="Services" :title="$home->services_heading ?: 'Our services'" id="services-title">
                    <x-slot:action><a href="{{ route('services.index') }}" class="btn btn-secondary">All services <x-icon name="arrow-right" size="16" /></a></x-slot:action>
                </x-public.section-heading>
                <div class="mt-12 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($services as $service)<x-public.service-card :service="$service" />@endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($home->showsSection('projects') && $projects->isNotEmpty())
        <section class="section bg-canvas" aria-labelledby="projects-title">
            <div class="container-site">
                <x-public.section-heading :eyebrow="$projects->every(fn ($p) => $p->work_origin->value === 'concept') ? 'Sample projects' : 'Selected work'" :title="$home->projects_heading ?: 'Selected work'" id="projects-title">
                    <x-slot:action><a href="{{ route('projects.index') }}" class="btn btn-secondary">Explore more work <x-icon name="arrow-right" size="16" /></a></x-slot:action>
                </x-public.section-heading>
                <div class="mt-12 grid gap-x-8 gap-y-12 md:grid-cols-2">
                    @foreach ($projects as $project)<x-public.project-card :project="$project" :large="true" sizes="(min-width: 1024px) 580px, (min-width: 768px) 50vw, 100vw" />@endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($home->showsSection('packages') && $packages->isNotEmpty())
        <section class="section" aria-labelledby="packages-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="Packages" :title="$home->packages_heading ?: 'Packages'" id="packages-title" intro="Common starting points. Scope and price are always confirmed in a written proposal.">
                    <x-slot:action><a href="{{ route('packages') }}" class="btn btn-secondary">Compare packages <x-icon name="arrow-right" size="16" /></a></x-slot:action>
                </x-public.section-heading>
                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($packages as $package)<x-public.package-card :package="$package" :compact="true" />@endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($home->showsSection('about') && ($home->about_heading || $home->about_body))
        <section class="section bg-canvas" aria-labelledby="about-title">
            <div class="container-site grid gap-10 lg:grid-cols-12 lg:gap-16">
                <div class="lg:col-span-6">
                    <p class="eyebrow">About us</p>
                    <h2 id="about-title" class="h-section mt-4">{{ $home->about_heading }}</h2>
                </div>
                <div class="lg:col-span-6 lg:pt-10">
                    <div class="prose-brivia lead text-muted [&>p:first-child]:font-display [&>p:first-child]:text-[1.375rem] [&>p:first-child]:font-semibold [&>p:first-child]:leading-snug [&>p:first-child]:tracking-[-0.015em] [&>p:first-child]:text-ink">{{ \App\Support\StructuredText::toHtml($home->about_body) }}</div>
                    <a href="{{ route('about') }}" class="btn btn-secondary mt-8">Meet the team <x-icon name="arrow-right" size="16" /></a>
                </div>
            </div>
        </section>
    @endif

    @if ($home->showsSection('process') && ! empty($home->process_steps))
        <section class="section" aria-labelledby="process-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="Delivery process" :title="$home->process_heading ?: 'How we work'" id="process-title" />
                <div class="mt-14"><x-public.process-steps :steps="$home->process_steps" /></div>
            </div>
        </section>
    @endif

    @if ($home->showsSection('final_cta'))
        <x-public.cta-strip :heading="$home->final_cta_heading ?: 'Ready to bring your idea to life?'" :body="$home->final_cta_body" />
    @endif
</x-layouts.public>
