@php
    $links = $project->projectMedia;
    $cover = $links->firstWhere('role', \App\Enums\MediaRole::Cover);
    $gallery = $links->where('role', \App\Enums\MediaRole::Gallery);
@endphp
<section class="card space-y-5" aria-labelledby="images-heading" id="images">
    <h2 id="images-heading" class="h-card">Images</h2>
    <p class="text-meta text-muted">JPEG, PNG or WebP, up to 5 MB and 6000 × 6000 px. Images are re-encoded to WebP and metadata is removed. Use only approved images.</p>

    @if ($links->isNotEmpty())
        <form method="POST" action="{{ route('admin.projects.media.update', $project) }}" class="space-y-3">
            @csrf @method('PUT')
            @foreach ($links as $link)
                <div class="grid gap-3 rounded-[10px] border border-border p-3 sm:grid-cols-[8rem_minmax(0,1fr)]">
                    <img src="{{ $link->media->url(480) }}" alt="" width="128" height="80" class="h-20 w-32 rounded-[6px] object-cover">
                    <div class="grid gap-3 md:grid-cols-2">
                        <p class="md:col-span-2"><span class="chip {{ $link->role->value === 'cover' ? 'chip-blue' : '' }}">{{ ucfirst($link->role->value) }}</span></p>
                        <x-form.field name="media[{{ $link->id }}][alt_text]" label="Alternative text" :value="$link->media->alt_text" required maxlength="240" />
                        <x-form.field name="media[{{ $link->id }}][caption]" label="Caption" :value="$link->caption" maxlength="240" />
                        <x-form.field name="media[{{ $link->id }}][sort_order]" label="Order" type="number" :value="$link->sort_order" required min="0" />
                    </div>
                </div>
            @endforeach
            <button type="submit" class="btn btn-secondary">Save image details</button>
        </form>
        <div class="flex flex-wrap gap-2">
            @foreach ($links as $link)
                <form method="POST" action="{{ route('admin.projects.media.destroy', [$project, $link]) }}" data-confirm="Remove this image from the project?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><x-icon name="trash" size="14" /> Remove {{ $link->role->value }} {{ $link->role->value === 'gallery' ? '#'.$loop->index : '' }}</button>
                </form>
            @endforeach
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.projects.cover', $project) }}" enctype="multipart/form-data" class="space-y-3 rounded-[10px] border border-dashed border-border p-4">
            @csrf
            <h3 class="font-semibold">{{ $cover ? 'Replace cover' : 'Upload cover' }}</h3>
            <x-form.field name="cover" label="Cover image (min. 1200 × 675 px)" type="file" accept="image/jpeg,image/png,image/webp" required />
            <x-form.field name="cover_alt" label="Alternative text" required maxlength="240" />
            <button type="submit" class="btn btn-secondary" data-loading-text="Uploading…"><span data-label>Upload cover</span></button>
        </form>
        <form method="POST" action="{{ route('admin.projects.gallery', $project) }}" enctype="multipart/form-data" class="space-y-3 rounded-[10px] border border-dashed border-border p-4">
            @csrf
            <h3 class="font-semibold">Add gallery image ({{ $gallery->count() }}/{{ config('brivia.media.max_gallery_images') }})</h3>
            <x-form.field name="gallery_image" label="Gallery image (min. 800 × 450 px)" type="file" accept="image/jpeg,image/png,image/webp" required />
            <x-form.field name="gallery_alt" label="Alternative text" required maxlength="240" />
            <x-form.field name="gallery_caption" label="Caption" maxlength="240" />
            <button type="submit" class="btn btn-secondary" data-loading-text="Uploading…"><span data-label>Add image</span></button>
        </form>
    </div>
</section>
