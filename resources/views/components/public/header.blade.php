@php
    $links = [
        ['Home', 'home', 'home'],
        ['Services', 'services.index', 'services.*'],
        ['Projects', 'projects.index', 'projects.*'],
        ['Packages', 'packages', 'packages'],
        ['About Us', 'about', 'about'],
        ['Contact', 'contact', 'contact'],
    ];
@endphp
<header class="on-dark sticky top-0 z-40 border-b border-white/[0.08] bg-navy-950 text-white">
    <div class="container-site flex h-[4.25rem] items-center justify-between gap-6 lg:h-[4.75rem]">
        <x-brand.lockup />

        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/20 transition hover:border-white/50 lg:hidden" data-nav-toggle aria-controls="site-nav" aria-expanded="false">
            <x-icon name="menu" size="20" class="icon-open" />
            <x-icon name="x" size="20" class="icon-close" />
            <span class="sr-only" data-nav-toggle-label>Open menu</span>
        </button>

        <nav id="site-nav" data-nav-panel aria-label="Main"
             class="lg-always absolute inset-x-0 top-full border-b border-white/10 bg-navy-950 lg:static lg:border-0 lg:bg-transparent">
            <div class="container-site pb-7 pt-3 lg:flex lg:items-center lg:gap-8 lg:p-0">
                <ul class="divide-y divide-white/[0.08] lg:flex lg:items-center lg:gap-1 lg:divide-y-0">
                    @foreach ($links as [$label, $route, $pattern])
                        @php($active = request()->routeIs($pattern))
                        <li>
                            <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                               class="group relative flex items-center justify-between py-4 text-[1.0625rem] font-medium transition-colors lg:px-3 lg:py-2 lg:text-[0.9375rem] {{ $active ? 'text-white' : 'text-on-dark-muted hover:text-white' }}">
                                {{ $label }}
                                <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-cyan lg:absolute lg:inset-x-3 lg:-bottom-[1.1rem] lg:h-0.5 lg:w-auto lg:rounded-none {{ $active ? '' : 'hidden' }}"></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:mt-0 lg:flex lg:items-center lg:border-l lg:border-white/10 lg:pl-8">
                    <a href="{{ route('consultation') }}" class="btn btn-primary btn-lg lg:btn-sm lg:hidden">Book a consultation <x-icon name="arrow-right" size="18" /></a>
                    <a href="{{ route('contact') }}" class="btn btn-secondary btn-lg lg:hidden">Discuss your project</a>
                    <a href="{{ route('consultation') }}" @if (request()->routeIs('consultation')) aria-current="page" @endif class="btn btn-quiet btn-sm hidden lg:inline-flex">
                        Book a consultation <x-icon name="arrow-right" size="16" />
                    </a>
                </div>
            </div>
        </nav>
    </div>
</header>
