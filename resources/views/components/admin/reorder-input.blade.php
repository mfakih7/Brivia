@props(['item'])
<label class="sr-only" for="order-{{ $item->id }}">Position for {{ $item->title ?? $item->name ?? $item->question }}</label>
<input id="order-{{ $item->id }}" type="number" name="order[{{ $item->id }}]" value="{{ $item->sort_order }}" min="0" max="100000" form="reorder-form" class="form-control !min-h-10 w-24 !py-1.5">
