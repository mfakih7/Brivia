@props(['name', 'label', 'items' => [], 'max' => 20, 'maxlength' => 200, 'hint' => null, 'blank' => 2])
@php
    $values = old($name, $items);
    $values = is_array($values) ? array_values($values) : [];
    $rows = array_merge($values, array_fill(0, max(0, min($blank, $max - count($values))), ''));
    $error = $errors->first($name) ?: $errors->first($name.'.*');
@endphp
<fieldset data-repeater data-max="{{ $max }}" {{ $attributes }}>
    <legend class="form-label">{{ $label }}</legend>
    @if ($hint)<p class="form-hint !mt-0 mb-2">{{ $hint }}</p>@endif
    <ol data-repeater-list class="space-y-2">
        @foreach ($rows as $i => $value)
            <li data-repeater-row class="flex gap-2">
                <label class="sr-only" for="{{ $name }}-{{ $i }}" data-for="{{ $name }}[__INDEX__]">{{ $label }}, item {{ $i + 1 }}</label>
                <input type="text" id="{{ $name }}-{{ $i }}" name="{{ $name }}[{{ $i }}]" data-name="{{ $name }}[__INDEX__]" value="{{ is_string($value) ? $value : '' }}" maxlength="{{ $maxlength }}" class="form-control" @if ($errors->has($name.'.'.$i)) aria-invalid="true" @endif>
                <button type="button" class="btn btn-secondary btn-sm shrink-0" data-repeater-remove><x-icon name="x" size="16" /><span class="sr-only">Remove item</span></button>
            </li>
        @endforeach
    </ol>
    <template>
        <li data-repeater-row class="flex gap-2">
            <label class="sr-only" data-for="{{ $name }}[__INDEX__]">{{ $label }}, new item</label>
            <input type="text" data-name="{{ $name }}[__INDEX__]" maxlength="{{ $maxlength }}" class="form-control">
            <button type="button" class="btn btn-secondary btn-sm shrink-0" data-repeater-remove><x-icon name="x" size="16" /><span class="sr-only">Remove item</span></button>
        </li>
    </template>
    <button type="button" class="btn btn-secondary btn-sm mt-2" data-repeater-add hidden><x-icon name="plus" size="16" /> Add item</button>
    @if ($error)<p class="form-error">{{ $error }}</p>@endif
    <p class="form-hint">Empty rows are ignored. Up to {{ $max }} items.</p>
</fieldset>
