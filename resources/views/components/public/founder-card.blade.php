@props(['member'])
<article class="flex h-full flex-col rounded-[18px] border border-line bg-surface p-6 md:p-8">
    <div class="flex items-center gap-5">
        @if ($member->portrait)
            <x-public.media-img :media="$member->portrait" sizes="88px" class="h-[5.5rem] w-[5.5rem] shrink-0 rounded-full object-cover" />
        @else
            <span class="flex h-[5.5rem] w-[5.5rem] shrink-0 items-center justify-center rounded-full bg-navy-950 font-display text-2xl font-bold tracking-wide text-white" aria-hidden="true">{{ $member->initials() }}</span>
        @endif
        <div class="min-w-0">
            <h3 class="font-display text-[1.25rem] font-bold leading-snug tracking-[-0.015em]">{{ $member->name }}</h3>
            <p class="text-muted">{{ $member->role_title }}</p>
            @if ($member->years_experience)<p class="text-meta mt-1 font-semibold text-primary-strong">{{ $member->years_experience }}+ years of experience</p>@endif
        </div>
    </div>
    @if ($member->biography)<div class="prose-brivia mt-6 text-muted">{{ \App\Support\StructuredText::toHtml($member->biography) }}</div>@endif
    @if ($member->skills)
        <ul class="mt-6 flex flex-wrap gap-2 border-t border-line pt-6" aria-label="Skills">
            @foreach ($member->skills as $skill)<li class="chip">{{ $skill }}</li>@endforeach
        </ul>
    @endif
    @if ($member->social_links)
        <ul class="mt-5 flex flex-wrap gap-2">
            @foreach ($member->social_links as $network => $url)
                <li><a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><x-icon :name="$network" size="16" /> {{ \App\Models\TeamMember::SOCIAL_NETWORKS[$network] ?? $network }}<span class="sr-only"> profile of {{ $member->name }} (opens in a new tab)</span></a></li>
            @endforeach
        </ul>
    @endif
</article>
