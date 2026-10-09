@props(['name', 'label', 'value' => null, 'required' => false, 'hint' => null, 'id' => null, 'rows' => 5])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f-'.str_replace('.', '-', $key);
    $error = $errors->first($key);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }}
        @if ($required)<span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(required)</span>@else<span class="font-normal text-muted text-sm"> (optional)</span>@endif
    </label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'form-control']) }}>{{ old($key, $value) }}</textarea>
    @if ($hint)<p id="{{ $id }}-hint" class="form-hint">{{ $hint }}</p>@endif
    @if ($error)<p id="{{ $id }}-error" class="form-error">{{ $error }}</p>@endif
</div>
