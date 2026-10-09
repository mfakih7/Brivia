<x-layouts.public :title="$active ? $active->name.' projects' : 'Projects'" description="Case studies and sample concepts. Each project is clearly labelled with its origin." :canonical="$projects->currentPage() > 1 || $active ? request()->fullUrlWithQuery([]) : route('projects.index')">
    <x-public.page-header eyebrow="Projects" title="Ideas brought to life" intro="Selected work and examples. Each project is labelled: BRIVIA projects, earlier work from our founders' careers, or sample concepts that illustrate what we can build." />

    <section class="section" aria-labelledby="projects-list-title">
        <div class="container-site">
            <h2 id="projects-list-title" class="sr-only">Project list</h2>
            @if ($categories->isNotEmpty())
                <nav aria-label="Filter projects by category" class="-mx-1 mb-12 overflow-x-auto px-1 pb-1">
                    <ul class="flex w-max gap-2">
                        @php($chip = 'inline-flex min-h-11 items-center rounded-full border px-5 text-[0.9375rem] font-semibold transition')
                        <li><a href="{{ route('projects.index') }}" @if (! $active) aria-current="page" @endif class="{{ $chip }} {{ ! $active ? 'border-navy-950 bg-navy-950 text-white' : 'border-line text-ink hover:border-ink/40' }}">All work</a></li>
                        @foreach ($categories as $category)
                            <li><a href="{{ route('projects.index', ['category' => $category->slug]) }}" @if ($active?->is($category)) aria-current="page" @endif class="{{ $chip }} {{ $active?->is($category) ? 'border-navy-950 bg-navy-950 text-white' : 'border-line text-ink hover:border-ink/40' }}">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($projects->isEmpty())
                <div class="rounded-[18px] border border-line p-10 text-center">
                    <p class="font-semibold">Our portfolio is being prepared.</p>
                    <p class="mt-1 text-muted">We only publish work we have permission to share. <a class="btn-link" href="{{ route('contact') }}">Ask us about relevant experience</a>.</p>
                </div>
            @else
                <p class="text-meta mb-8 text-muted" role="status">Showing {{ $projects->firstItem() }}–{{ $projects->lastItem() }} of {{ $projects->total() }} {{ \Illuminate\Support\Str::plural('project', $projects->total()) }}{{ $active ? ' in '.$active->name : '' }}.</p>
                <div class="grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)<x-public.project-card :project="$project" headingLevel="h3" />@endforeach
                </div>
                @if ($projects->hasPages())
                    <nav class="mt-16 flex items-center justify-between gap-4 border-t border-line pt-8" aria-label="Project pages">
                        @if ($projects->onFirstPage())<span class="btn btn-secondary" aria-disabled="true"><x-icon name="arrow-left" size="16" /> Previous</span>@else<a class="btn btn-secondary" href="{{ $projects->previousPageUrl() }}" rel="prev"><x-icon name="arrow-left" size="16" /> Previous</a>@endif
                        <span class="text-meta text-muted">Page {{ $projects->currentPage() }} of {{ $projects->lastPage() }}</span>
                        @if ($projects->hasMorePages())<a class="btn btn-secondary" href="{{ $projects->nextPageUrl() }}" rel="next">Next <x-icon name="arrow-right" size="16" /></a>@else<span class="btn btn-secondary" aria-disabled="true">Next <x-icon name="arrow-right" size="16" /></span>@endif
                    </nav>
                @endif
            @endif
        </div>
    </section>

    <x-public.cta-strip heading="Have a similar idea?" />
</x-layouts.public>
