@props(['title'])
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
<body class="min-h-screen bg-navy-950 on-dark">
    <main class="flex min-h-screen items-center justify-center px-5 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8 flex justify-center"><x-brand.lockup :href="route('admin.login')" /></div>
            <div class="rounded-[16px] bg-white p-6 text-ink shadow-xl sm:p-8">
                <h1 class="font-display text-2xl font-extrabold">{{ $title }}</h1>
                <div class="mt-5">
                    <x-flash />
                    {{ $slot }}
                </div>
            </div>
            <p class="mt-6 text-center text-sm text-on-dark-muted">Staff access only. Activity is logged.</p>
        </div>
    </main>
</body>
</html>
