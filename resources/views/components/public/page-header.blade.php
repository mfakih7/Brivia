@props(['eyebrow' => null, 'title', 'intro' => null, 'crumbs' => [], 'compact' => false])
<section class="on-dark relative overflow-hidden bg-navy-950 text-white">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(48rem_22rem_at_100%_0%,rgba(0,107,255,0.22),transparent_70%)]" aria-hidden="true"></div>
    <div class="container-site relative {{ $compact ? 'pb-12 pt-10 lg:pb-14 lg:pt-14' : 'pb-14 pt-10 lg:pb-20 lg:pt-16' }}">
        @if ($crumbs)
            <nav aria-label="Breadcrumb" class="mb-8 text-sm text-on-dark-subtle">
                <ol class="flex flex-wrap items-center gap-2">
                    @foreach ($crumbs as $label => $href)
                        <li class="flex items-center gap-2"><a class="transition hover:text-white" href="{{ $href }}">{{ $label }}</a><span aria-hidden="true" class="text-white/30">/</span></li>
                    @endforeach
                    <li aria-current="page" class="text-on-dark-muted">{{ $title }}</li>
                </ol>
            </nav>
        @endif
        <div class="grid gap-6 lg:grid-cols-12 lg:items-end lg:gap-10">
            <div class="lg:col-span-7">
                @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
                <h1 class="h-page mt-4 break-words">{{ $title }}</h1>
            </div>
            @if ($intro)
                <p class="lead text-on-dark-muted lg:col-span-5 lg:pb-1.5">{{ $intro }}</p>
            @endif
        </div>
        @if (trim($slot) !== '')
            <div class="mt-8">{{ $slot }}</div>
        @endif
    </div>
</section>
