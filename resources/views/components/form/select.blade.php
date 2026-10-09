@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'hint' => null, 'id' => null, 'placeholder' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f-'.str_replace('.', '-', $key);
    $error = $errors->first($key);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
    $selected = (string) old($key, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }}
        @if ($required)<span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(required)</span>@else<span class="font-normal text-muted text-sm"> (optional)</span>@endif
    </label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'form-control']) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            @if (is_array($optionLabel))
                <optgroup label="{{ $optionValue }}">
                    @foreach ($optionLabel as $v => $l)
                        <option value="{{ $v }}" @selected($selected === (string) $v)>{{ $l }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endif
        @endforeach
    </select>
    @if ($hint)<p id="{{ $id }}-hint" class="form-hint">{{ $hint }}</p>@endif
    @if ($error)<p id="{{ $id }}-error" class="form-error">{{ $error }}</p>@endif
</div>
