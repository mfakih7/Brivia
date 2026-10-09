<x-layouts.admin title="Team">
    <x-admin.page-header title="Team" description="Founder and team cards on the About page. Use initials until approved photos are available.">
        <x-slot:actions><a href="{{ route('admin.team.create') }}" class="btn btn-primary"><x-icon name="plus" size="18" /> New member</a></x-slot:actions>
    </x-admin.page-header>
    <x-admin.list-toolbar :action="route('admin.team.index')" />
    @if ($members->isEmpty())
        <x-admin.empty-state title="No team members found" />
    @else
        <form id="reorder-form" method="POST" action="{{ route('admin.team.reorder') }}">@csrf</form>
        <x-admin.table label="Team list">
            <thead><tr><th scope="col">Order</th><th scope="col">Name</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($members as $member)
                    <tr>
                        <td><x-admin.reorder-input :item="$member" /></td>
                        <td><a class="font-semibold hover:underline" href="{{ route('admin.team.edit', $member) }}">{{ $member->name }}</a><div class="text-meta text-muted">{{ $member->role_title }}</div></td>
                        <td><x-admin.status-chip :item="$member" /></td>
                        <td class="whitespace-nowrap text-right"><a class="btn-link" href="{{ route('admin.team.edit', $member) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <button type="submit" form="reorder-form" class="btn btn-secondary btn-sm">Save order</button>
            {{ $members->links() }}
        </div>
    @endif
</x-layouts.admin>
