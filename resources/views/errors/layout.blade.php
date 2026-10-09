<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · BRIVIA</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public on-dark flex min-h-screen flex-col bg-navy-950 text-white">
    <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(56rem_28rem_at_90%_-10%,rgba(0,107,255,0.22),transparent_65%)]" aria-hidden="true"></div>
    <header class="container-site relative flex h-[4.75rem] items-center border-b border-white/[0.08]">
        <x-brand.lockup href="/" />
    </header>
    <main class="container-site relative flex flex-1 flex-col justify-center py-20" id="main">
        <p class="eyebrow">@yield('code')</p>
        <h1 class="h-page mt-5 max-w-3xl">@yield('title')</h1>
        <p class="lead mt-5 max-w-xl text-on-dark-muted">@yield('message')</p>
        <div class="mt-10 flex flex-col gap-3 sm:flex-row">
            <a href="/" class="btn btn-primary btn-lg">Go to home <x-icon name="arrow-right" size="18" /></a>
            <a href="/contact" class="btn btn-secondary btn-lg">Contact us</a>
        </div>
    </main>
    <footer class="container-site relative border-t border-white/[0.08] py-6 text-sm text-on-dark-subtle">&copy; {{ now()->year }} BRIVIA</footer>
</body>
</html>
