@props(['label'])
<div class="card overflow-hidden !p-0">
    <div class="relative overflow-x-auto" role="region" aria-label="{{ $label }}" tabindex="0">
        <table class="table-admin">
            {{ $slot }}
        </table>
    </div>
</div>
