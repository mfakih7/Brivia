<x-layouts.admin title="Projects">
    <x-admin.page-header title="Projects" description="Case studies require publication permission, a cover image with alt text and an accurate origin label.">
        <x-slot:actions>
            <a href="{{ route('admin.project-categories.index') }}" class="btn btn-secondary">Categories</a>
            <a href="{{ route('admin.projects.create') }}" class="btn btn-primary"><x-icon name="plus" size="18" /> New project</a>
        </x-slot:actions>
    </x-admin.page-header>
    <x-admin.list-toolbar :action="route('admin.projects.index')">
        <div>
            <label for="list-category" class="form-label">Category</label>
            <select id="list-category" name="category" class="form-control">
                <option value="">All</option>
                @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>@endforeach
            </select>
        </div>
    </x-admin.list-toolbar>

    @if ($projects->isEmpty())
        <x-admin.empty-state title="No projects yet">Only add work BRIVIA has permission to show. Label earlier founder work and sample concepts accurately.</x-admin.empty-state>
    @else
        <form id="reorder-form" method="POST" action="{{ route('admin.projects.reorder') }}">@csrf</form>
        <x-admin.table label="Projects list">
            <thead><tr><th scope="col">Order</th><th scope="col">Project</th><th scope="col">Origin</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($projects as $project)
                    <tr>
                        <td><x-admin.reorder-input :item="$project" /></td>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($project->cover?->media)
                                    <img src="{{ $project->cover->media->url(480) }}" alt="" width="64" height="40" class="h-10 w-16 rounded-[6px] object-cover">
                                @else
                                    <span class="flex h-10 w-16 items-center justify-center rounded-[6px] bg-canvas text-muted"><x-icon name="image" size="18" /></span>
                                @endif
                                <div><a class="font-semibold hover:underline" href="{{ route('admin.projects.edit', $project) }}">{{ $project->title }}</a><div class="text-meta text-muted">{{ $project->category->name }}</div></div>
                            </div>
                        </td>
                        <td><span class="chip">{{ $project->work_origin->label() }}</span> @unless ($project->permission_confirmed)<span class="chip chip-red">No permission</span>@endunless</td>
                        <td><x-admin.status-chip :item="$project" /></td>
                        <td class="whitespace-nowrap text-right"><a class="btn-link" href="{{ route('admin.projects.edit', $project) }}">Edit</a> · <a class="btn-link" href="{{ route('admin.projects.preview', $project) }}">Preview</a></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <button type="submit" form="reorder-form" class="btn btn-secondary btn-sm">Save order</button>
            {{ $projects->links() }}
        </div>
    @endif
</x-layouts.admin>
