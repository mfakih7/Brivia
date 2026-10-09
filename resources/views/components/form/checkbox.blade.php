@props(['name', 'label', 'checked' => false, 'hint' => null, 'id' => null, 'value' => '1', 'required' => false])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f-'.str_replace('.', '-', $key);
    $error = $errors->first($key);
    $isChecked = old() ? (bool) old($key) : (bool) $checked;
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp
<div {{ $attributes->only('class') }}>
    <div class="form-check">
        @unless ($required)<input type="hidden" name="{{ $name }}" value="0">@endunless
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" @checked($isChecked) @required($required)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        <label for="{{ $id }}" class="text-[0.9375rem]">{{ $label }} @if ($required)<span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif</label>
    </div>
    @if ($hint)<p id="{{ $id }}-hint" class="form-hint ml-8">{{ $hint }}</p>@endif
    @if ($error)<p id="{{ $id }}-error" class="form-error ml-8">{{ $error }}</p>@endif
</div>
