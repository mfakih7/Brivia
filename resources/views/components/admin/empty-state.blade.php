@props(['title' => 'Nothing here yet'])
<div class="card text-center">
    <p class="font-semibold">{{ $title }}</p>
    <div class="mt-1 text-muted">{{ $slot }}</div>
</div>
