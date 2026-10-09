@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'id' => null,
])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f-'.str_replace('.', '-', $key);
    $error = $errors->first($key);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
    $current = $type === 'password' ? null : old($key, $value);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }}
        @if ($required)<span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(required)</span>@else<span class="font-normal text-muted text-sm"> (optional)</span>@endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($current !== null) value="{{ $current }}" @endif
        @required($required)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'form-control']) }}
    >
    @if ($hint)<p id="{{ $id }}-hint" class="form-hint">{{ $hint }}</p>@endif
    @if ($error)<p id="{{ $id }}-error" class="form-error">{{ $error }}</p>@endif
</div>
