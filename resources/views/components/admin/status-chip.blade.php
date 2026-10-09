@props(['item'])
@if ($item->isPublished())
    <span class="chip chip-green"><x-icon name="check" size="14" /> Published</span>
@elseif ($item->status?->value === 'published')
    <span class="chip chip-blue">Scheduled</span>
@else
    <span class="chip chip-amber">Draft</span>
@endif
@if ($item->is_placeholder ?? false)
    <span class="chip chip-red">Placeholder</span>
@endif
