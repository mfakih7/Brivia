<x-layouts.admin title="Consultation types">
    <x-admin.page-header title="Consultation types" description="Options visitors can choose when requesting a consultation. Times are always confirmed manually.">
        <x-slot:actions><a href="{{ route('admin.consultation-types.create') }}" class="btn btn-primary"><x-icon name="plus" size="18" /> New type</a></x-slot:actions>
    </x-admin.page-header>
    <x-admin.list-toolbar :action="route('admin.consultation-types.index')" />
    @if ($types->isEmpty())
        <x-admin.empty-state title="No consultation types found" />
    @else
        <form id="reorder-form" method="POST" action="{{ route('admin.consultation-types.reorder') }}">@csrf</form>
        <x-admin.table label="Consultation types list">
            <thead><tr><th scope="col">Order</th><th scope="col">Name</th><th scope="col">Duration</th><th scope="col">Price</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @foreach ($types as $type)
                    <tr>
                        <td><x-admin.reorder-input :item="$type" /></td>
                        <td><a class="font-semibold hover:underline" href="{{ route('admin.consultation-types.edit', $type) }}">{{ $type->name }}</a><div class="text-meta text-muted">{{ $type->appointments_count }} requests</div></td>
                        <td>{{ $type->duration_minutes }} min</td>
                        <td>{{ $type->priceLabel() }}</td>
                        <td><x-admin.status-chip :item="$type" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <button type="submit" form="reorder-form" class="btn btn-secondary btn-sm">Save order</button>
            {{ $types->links() }}
        </div>
    @endif
</x-layouts.admin>
