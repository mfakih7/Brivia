@props(['name', 'label', 'items' => [], 'max' => 8, 'blank' => 1, 'titleLabel' => 'Title', 'bodyLabel' => 'Description'])
@php
    $values = old($name, $items);
    $values = is_array($values) ? array_values(array_filter($values, 'is_array')) : [];
    $rows = array_merge($values, array_fill(0, max(0, min($blank, $max - count($values))), ['title' => '', 'body' => '']));
    $error = $errors->first($name) ?: $errors->first($name.'.*');
@endphp
<fieldset data-repeater data-max="{{ $max }}">
    <legend class="form-label">{{ $label }}</legend>
    <ol data-repeater-list class="space-y-3">
        @foreach ($rows as $i => $row)
            <li data-repeater-row class="rounded-[10px] border border-border bg-canvas p-3">
                <div class="grid gap-3 md:grid-cols-[14rem_minmax(0,1fr)]">
                    <div>
                        <label class="form-label text-sm" for="{{ $name }}-{{ $i }}-title" data-for="{{ $name }}[__INDEX__][title]">{{ $titleLabel }}</label>
                        <input type="text" id="{{ $name }}-{{ $i }}-title" name="{{ $name }}[{{ $i }}][title]" data-name="{{ $name }}[__INDEX__][title]" value="{{ $row['title'] ?? '' }}" maxlength="80" class="form-control">
                    </div>
                    <div>
                        <label class="form-label text-sm" for="{{ $name }}-{{ $i }}-body" data-for="{{ $name }}[__INDEX__][body]">{{ $bodyLabel }}</label>
                        <textarea id="{{ $name }}-{{ $i }}-body" name="{{ $name }}[{{ $i }}][body]" data-name="{{ $name }}[__INDEX__][body]" rows="2" maxlength="400" class="form-control !min-h-12">{{ $row['body'] ?? '' }}</textarea>
                    </div>
                </div>
                <button type="button" class="btn-link mt-2 text-sm" data-repeater-remove><x-icon name="trash" size="14" /> Remove</button>
            </li>
        @endforeach
    </ol>
    <template>
        <li data-repeater-row class="rounded-[10px] border border-border bg-canvas p-3">
            <div class="grid gap-3 md:grid-cols-[14rem_minmax(0,1fr)]">
                <div><label class="form-label text-sm" data-for="{{ $name }}[__INDEX__][title]">{{ $titleLabel }}</label><input type="text" data-name="{{ $name }}[__INDEX__][title]" maxlength="80" class="form-control"></div>
                <div><label class="form-label text-sm" data-for="{{ $name }}[__INDEX__][body]">{{ $bodyLabel }}</label><textarea data-name="{{ $name }}[__INDEX__][body]" rows="2" maxlength="400" class="form-control !min-h-12"></textarea></div>
            </div>
            <button type="button" class="btn-link mt-2 text-sm" data-repeater-remove><x-icon name="trash" size="14" /> Remove</button>
        </li>
    </template>
    <button type="button" class="btn btn-secondary btn-sm mt-2" data-repeater-add hidden><x-icon name="plus" size="16" /> Add row</button>
    @if ($error)<p class="form-error">{{ $error }}</p>@endif
    <p class="form-hint">Rows with an empty title and description are ignored. Up to {{ $max }} rows.</p>
</fieldset>
