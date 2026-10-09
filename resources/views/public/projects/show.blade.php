@php($cover = $project->cover?->media)
<x-layouts.public :title="$project->meta_title ?: $project->title" :description="$project->meta_description ?: $project->summary" :og-image="$cover?->url(1600)" :preview="$preview ?? false">
    <x-public.page-header :eyebrow="$project->category->name" :title="$project->title" :intro="$project->summary" :crumbs="['Home' => route('home'), 'Projects' => route('projects.index')]">
        <ul class="flex flex-wrap gap-2">
            @if ($badge = $project->work_origin->publicBadge())<li class="chip {{ $project->work_origin->value === 'concept' ? 'chip-amber' : 'chip-blue' }}">{{ $badge }}</li>@endif
            @if ($project->client_display_name)<li class="chip">Client: {{ $project->client_display_name }}</li>@endif
        </ul>
    </x-public.page-header>

    @if ($cover)
        <div class="bg-[linear-gradient(to_bottom,var(--color-navy-950)_0,var(--color-navy-950)_3rem,transparent_3rem)] lg:bg-[linear-gradient(to_bottom,var(--color-navy-950)_0,var(--color-navy-950)_6rem,transparent_6rem)]">
            <div class="container-site">
                <figure>
                    <x-public.media-img :media="$cover" :eager="true" sizes="(min-width: 1264px) 1200px, 100vw" class="aspect-[16/9] w-full rounded-[20px] object-cover" />
                    @if ($project->cover->caption)<figcaption class="text-meta mt-3 text-muted">{{ $project->cover->caption }}</figcaption>@endif
                </figure>
            </div>
        </div>
    @endif

    <section class="section">
        <div class="container-site">
            @if ($project->work_origin->value === 'concept')
                <p class="alert alert-warning mb-12 max-w-3xl">This is a sample concept created to illustrate the kind of product we can build. It is not client work.</p>
            @elseif ($project->work_origin->value === 'founder_experience')
                <p class="alert alert-info mb-12 max-w-3xl">This project comes from our founders' earlier experience, before BRIVIA was founded.</p>
            @endif

            <div class="grid gap-14 lg:grid-cols-12 lg:gap-10">
                <div class="space-y-14 lg:col-span-7">
                    @php($n = 0)
                    @foreach (['problem' => 'The context', 'contribution' => 'Our role', 'approach' => 'Approach', 'solution' => 'Solution', 'outcomes' => 'Outcomes'] as $field => $heading)
                        @if (filled($project->{$field}))
                            <div class="grid gap-3 md:grid-cols-[4rem_1fr]">
                                <span class="index-num md:pt-2" aria-hidden="true">{{ str_pad(++$n, 2, '0', STR_PAD_LEFT) }}</span>
                                <div>
                                    <h2 class="font-display text-[1.625rem] font-bold leading-tight tracking-[-0.02em]">{{ $heading }}</h2>
                                    <div class="prose-brivia mt-4 text-[1.0625rem] leading-[1.75]">{{ \App\Support\StructuredText::toHtml($project->{$field}) }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <aside class="lg:col-span-4 lg:col-start-9" aria-label="Project facts">
                    <div class="rounded-[18px] border border-line p-6 md:p-8 lg:sticky lg:top-28">
                        <dl class="space-y-5 text-[0.9375rem]">
                            <div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Category</dt><dd class="mt-1 font-semibold">{{ $project->category->name }}</dd></div>
                            @if ($badge = $project->work_origin->publicBadge())<div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Origin</dt><dd class="mt-1 font-semibold">{{ $badge }}</dd></div>@endif
                            @if ($project->technologies)
                                <div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Technologies</dt><dd class="mt-2"><ul class="flex flex-wrap gap-2">@foreach ($project->technologies as $tech)<li class="chip">{{ $tech }}</li>@endforeach</ul></dd></div>
                            @endif
                            @if ($project->services->isNotEmpty())
                                <div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">Services</dt><dd class="mt-2"><ul class="space-y-1.5">@foreach ($project->services as $service)<li><a class="btn-link" href="{{ route('services.show', $service->slug) }}">{{ $service->title }}</a></li>@endforeach</ul></dd></div>
                            @endif
                        </dl>
                        <div class="mt-7 space-y-3 border-t border-line pt-7">
                            @if ($project->website_url)
                                <a href="{{ $project->website_url }}" class="btn btn-secondary w-full" target="_blank" rel="noopener nofollow">Visit the website <x-icon name="external" size="16" /><span class="sr-only"> (opens in a new tab)</span></a>
                            @endif
                            <a href="{{ route('contact', ['project' => $project->slug]) }}" class="btn btn-primary w-full">Discuss a similar project <x-icon name="arrow-right" size="16" /></a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @if ($project->gallery->isNotEmpty())
        <section class="section bg-canvas" aria-labelledby="gallery-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="Gallery" title="A closer look" id="gallery-title" />
                <ul class="mt-12 grid gap-6 md:grid-cols-2">
                    @foreach ($project->gallery as $item)
                        <li>
                            <figure>
                                <a href="{{ $item->media->url() }}" class="block overflow-hidden rounded-[18px] border border-line bg-surface" target="_blank" rel="noopener">
                                    <x-public.media-img :media="$item->media" sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[16/10] w-full object-cover transition duration-500 hover:scale-[1.02]" />
                                    <span class="sr-only"> — open full-size image (new tab)</span>
                                </a>
                                @if ($item->caption)<figcaption class="text-meta mt-3 text-muted">{{ $item->caption }}</figcaption>@endif
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="section" aria-labelledby="more-work">
            <div class="container-site">
                <x-public.section-heading eyebrow="More work" title="Related projects" id="more-work">
                    <x-slot:action><a href="{{ route('projects.index') }}" class="btn btn-secondary">All projects <x-icon name="arrow-right" size="16" /></a></x-slot:action>
                </x-public.section-heading>
                <div class="mt-12 grid gap-x-8 gap-y-12 md:grid-cols-2">
                    @foreach ($related as $other)<x-public.project-card :project="$other" :large="true" />@endforeach
                </div>
            </div>
        </section>
    @endif

    <x-public.cta-strip heading="Have a similar project in mind?" />
</x-layouts.public>
