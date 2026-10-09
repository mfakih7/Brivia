@props(['project', 'headingLevel' => 'h3', 'sizes' => '(min-width: 1024px) 380px, (min-width: 768px) 50vw, 100vw', 'large' => false])
<article class="group relative flex h-full flex-col">
    <div class="overflow-hidden rounded-[18px] bg-navy-900 {{ $large ? 'aspect-[16/10]' : 'aspect-[4/3]' }}">
        @if ($project->cover?->media)
            <x-public.media-img :media="$project->cover->media" :sizes="$sizes" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" alt="" />
        @else
            <div class="flex h-full items-center justify-center text-on-dark-muted" aria-hidden="true"><x-icon name="image" size="40" /></div>
        @endif
    </div>
    <div class="flex flex-1 flex-col pt-5">
        <p class="text-meta flex flex-wrap items-center gap-x-3 gap-y-1 text-muted">
            <span>{{ $project->category->name }}</span>
            @if ($badge = $project->work_origin->publicBadge())<span class="chip {{ $project->work_origin->value === 'concept' ? 'chip-amber' : 'chip-blue' }}">{{ $badge }}</span>@endif
        </p>
        <{{ $headingLevel }} class="mt-2 font-display font-bold tracking-[-0.015em] {{ $large ? 'text-[1.5rem] leading-tight' : 'text-[1.1875rem] leading-snug' }}">
            <a href="{{ route('projects.show', $project->slug) }}" class="after:absolute after:inset-0 after:content-[''] group-hover:text-primary-strong">{{ $project->title }}</a>
        </{{ $headingLevel }}>
        <p class="mt-2 text-muted">{{ $project->summary }}</p>
        <span class="btn-link mt-4" aria-hidden="true">View case study <x-icon name="arrow-right" size="16" /></span>
    </div>
</article>
