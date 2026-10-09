@props(['steps'])
@php($cols = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][count($steps)] ?? 'lg:grid-cols-5')
<ol class="grid gap-x-8 gap-y-10 sm:grid-cols-2 {{ $cols }}">
    @foreach ($steps as $i => $step)
        <li class="relative border-t border-ink/15 pt-6">
            <span class="absolute -top-[5px] left-0 h-2.5 w-2.5 rounded-full border-2 border-primary bg-surface" aria-hidden="true"></span>
            <span class="index-num" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
            <h3 class="h-card mt-3"><span class="sr-only">Step {{ $i + 1 }}: </span>{{ $step['title'] }}</h3>
            @if (! empty($step['body']))<p class="mt-2 text-[0.9375rem] text-muted">{{ $step['body'] }}</p>@endif
        </li>
    @endforeach
</ol>
