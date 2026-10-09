<x-layouts.admin title="Services">
    <x-admin.page-header title="Services" description="Published services appear on the home page, services overview and their own detail page.">
        <x-slot:actions><a href="{{ route('admin.services.create') }}" class="btn btn-primary"><x-icon name="plus" size="18" /> New service</a></x-slot:actions>
    </x-admin.page-header>
    <x-admin.list-toolbar :action="route('admin.services.index')" />

    @if ($services->isEmpty())
        <x-admin.empty-state title="No services found">Try clearing filters, or create a new service.</x-admin.empty-state>
    @else
        <form id="reorder-form" method="POST" action="{{ route('admin.services.reorder') }}">@csrf</form>
        <x-admin.table label="Services list">
            <thead><tr><th scope="col">Order</th><th scope="col">Title</th><th scope="col">Status</th><th scope="col">Used by</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($services as $service)
                    <tr>
                        <td><x-admin.reorder-input :item="$service" /></td>
                        <td><a class="font-semibold hover:underline" href="{{ route('admin.services.edit', $service) }}">{{ $service->title }}</a><div class="text-meta text-muted">/services/{{ $service->slug }}</div></td>
                        <td><x-admin.status-chip :item="$service" /></td>
                        <td class="text-meta text-muted whitespace-nowrap">{{ $service->packages_count }} packages · {{ $service->projects_count }} projects</td>
                        <td class="whitespace-nowrap text-right"><a class="btn-link" href="{{ route('admin.services.edit', $service) }}">Edit</a> · <a class="btn-link" href="{{ route('admin.services.preview', $service) }}">Preview</a></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <button type="submit" form="reorder-form" class="btn btn-secondary btn-sm">Save order</button>
            {{ $services->links() }}
        </div>
    @endif
</x-layouts.admin>
