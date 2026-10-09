@php($title = $model->title ?? $model->name ?? 'Preview')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Preview: {{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas">
    <div class="bg-amber-100 px-4 py-3 text-center text-sm font-semibold text-amber-900" role="status">Preview of saved content — {{ $model->isPublished() ? 'published' : 'draft, not visible to the public' }}. This page is private.</div>
    <main class="container-site section">
        <h1 class="h-page">{{ $title }}</h1>
        @if ($model->summary ?? null)<p class="mt-4 text-lg text-muted">{{ $model->summary }}</p>@endif
        <div class="prose-brivia mt-6 max-w-3xl">{{ \App\Support\StructuredText::toHtml($model->body ?? $model->biography ?? $model->solution ?? '') }}</div>
    </main>
</body>
</html>
