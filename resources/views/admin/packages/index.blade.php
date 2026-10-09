<x-layouts.admin title="Packages">
    <x-admin.page-header title="Packages" description="Starting points shown on the packages page and homepage preview. Selecting a package on the website is never a purchase.">
        <x-slot:actions><a href="{{ route('admin.packages.create') }}" class="btn btn-primary"><x-icon name="plus" size="18" /> New package</a></x-slot:actions>
    </x-admin.page-header>
    <x-admin.list-toolbar :action="route('admin.packages.index')" />

    @if ($packages->isEmpty())
        <x-admin.empty-state title="No packages found">Try clearing filters, or create a new package.</x-admin.empty-state>
    @else
        <form id="reorder-form" method="POST" action="{{ route('admin.packages.reorder') }}">@csrf</form>
        <x-admin.table label="Packages list">
            <thead><tr><th scope="col">Order</th><th scope="col">Name</th><th scope="col">Price shown</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($packages as $package)
                    <tr>
                        <td><x-admin.reorder-input :item="$package" /></td>
                        <td><a class="font-semibold hover:underline" href="{{ route('admin.packages.edit', $package) }}">{{ $package->name }}</a> @if ($package->is_featured)<span class="chip chip-blue ml-1">Featured</span>@endif</td>
                        <td>{{ $package->priceLabel() }}</td>
                        <td><x-admin.status-chip :item="$package" /></td>
                        <td class="whitespace-nowrap text-right"><a class="btn-link" href="{{ route('admin.packages.edit', $package) }}">Edit</a> · <a class="btn-link" href="{{ route('admin.packages.preview', $package) }}">Preview</a></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <button type="submit" form="reorder-form" class="btn btn-secondary btn-sm">Save order</button>
            {{ $packages->links() }}
        </div>
    @endif
</x-layouts.admin>
