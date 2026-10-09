<x-layouts.admin title="Project categories">
    <x-admin.page-header title="Project categories" description="Used as portfolio filters. Categories in use cannot be deleted.">
        <x-slot:actions><a href="{{ route('admin.projects.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> Projects</a></x-slot:actions>
    </x-admin.page-header>
    <x-form.error-summary />
    <div class="space-y-3">
        @foreach ($categories as $category)
            <div class="card flex flex-wrap items-end gap-3 !p-4">
                <form method="POST" action="{{ route('admin.project-categories.update', $category) }}" class="flex flex-1 flex-wrap items-end gap-3">
                    @csrf @method('PUT')
                    <div class="min-w-[10rem] flex-1"><label class="form-label" for="cat-name-{{ $category->id }}">Name</label><input id="cat-name-{{ $category->id }}" name="name" value="{{ $category->name }}" maxlength="80" required class="form-control"></div>
                    <div class="min-w-[10rem] flex-1"><label class="form-label" for="cat-slug-{{ $category->id }}">Slug</label><input id="cat-slug-{{ $category->id }}" name="slug" value="{{ $category->slug }}" maxlength="100" class="form-control"></div>
                    <div class="w-28"><label class="form-label" for="cat-order-{{ $category->id }}">Order</label><input id="cat-order-{{ $category->id }}" type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" max="100000" class="form-control"></div>
                    <button type="submit" class="btn btn-secondary">Save</button>
                </form>
                <span class="text-meta text-muted">{{ $category->projects_count }} projects</span>
                <form method="POST" action="{{ route('admin.project-categories.destroy', $category) }}" data-confirm="Delete this category?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><x-icon name="trash" size="16" /><span class="sr-only">Delete {{ $category->name }}</span></button>
                </form>
            </div>
        @endforeach
    </div>
    <section class="card mt-6 max-w-2xl" aria-labelledby="new-cat">
        <h2 id="new-cat" class="h-card">Add category</h2>
        <form method="POST" action="{{ route('admin.project-categories.store') }}" class="mt-4 grid gap-4 sm:grid-cols-3">
            @csrf
            <x-form.field name="name" label="Name" required maxlength="80" />
            <x-form.field name="slug" label="Slug" maxlength="100" hint="Optional" />
            <x-form.field name="sort_order" label="Order" type="number" value="100" required min="0" />
            <div><button type="submit" class="btn btn-primary">Add category</button></div>
        </form>
    </section>
</x-layouts.admin>
