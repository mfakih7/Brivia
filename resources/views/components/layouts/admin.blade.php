@props(['title' => 'Dashboard'])
@php($navItems = \App\Support\AdminNavigation::itemsFor(auth()->user()))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ config('app.name') }} Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-ink">
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="min-h-screen lg:flex lg:bg-[linear-gradient(to_right,var(--color-navy-950)_15rem,transparent_15rem)]">
        <aside aria-label="Admin sidebar" class="on-dark bg-navy-950 text-white lg:sticky lg:top-0 lg:h-screen lg:w-60 lg:shrink-0 lg:overflow-y-auto">
            <div class="flex h-16 items-center justify-between px-4">
                <x-brand.lockup :href="route('admin.dashboard')" size="sm" />
                <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-[10px] border border-white/20 lg:hidden" data-nav-toggle aria-controls="admin-nav" aria-expanded="false">
                    <x-icon name="menu" />
                    <span class="sr-only" data-nav-toggle-label>Open menu</span>
                </button>
            </div>
            <nav id="admin-nav" data-nav-panel class="lg-always px-3 pb-6" aria-label="Admin sections">
                <ul class="space-y-0.5">
                    @foreach ($navItems as $item)
                        @php($active = request()->routeIs($item['pattern']))
                        <li>
                            <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                               class="flex items-center gap-3 rounded-[10px] border-l-[3px] px-3 py-2.5 text-[0.9375rem] {{ $active ? 'border-cyan bg-white/10 font-semibold text-white' : 'border-transparent text-on-dark-muted hover:bg-white/5 hover:text-white' }}">
                                <x-icon :name="$item['icon']" size="18" />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-border bg-white px-4 py-2 lg:px-8">
                <p class="text-sm text-muted">
                    Signed in as <span class="font-semibold text-ink">{{ auth()->user()->name }}</span>
                    <span class="chip ml-1">{{ auth()->user()->role->label() }}</span>
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.account.security') }}" class="btn btn-secondary btn-sm"><x-icon name="shield" size="16" /> Security</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="logout" size="16" /> Sign out</button>
                    </form>
                </div>
            </header>
            <main id="main" class="mx-auto max-w-[1200px] px-4 py-6 lg:px-8 lg:py-8" tabindex="-1">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
