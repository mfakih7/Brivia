<x-layouts.admin :title="$member->exists ? 'Edit team member' : 'New team member'">
    <x-admin.page-header :title="$member->exists ? $member->name : 'New team member'">
        <x-slot:actions><a href="{{ route('admin.team.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> Team</a></x-slot:actions>
    </x-admin.page-header>

    @if ($member->is_placeholder)
        <div class="alert alert-warning mb-6" role="status">This is placeholder content. Replace it with the approved founder details. In production it cannot be published until an owner marks it as approved.</div>
    @endif

    <x-admin.form-layout :action="$member->exists ? route('admin.team.update', $member) : route('admin.team.store')" :method="$member->exists ? 'PUT' : 'POST'" :model="$member" :submit="$member->isPublished() ? 'Save changes' : 'Save draft'">
        <section class="card space-y-4" aria-labelledby="basics-heading">
            <h2 id="basics-heading" class="h-card">Profile</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="name" label="Name" :value="$member->name" required maxlength="120" />
                <x-form.field name="role_title" label="Role" :value="$member->role_title" required maxlength="160" />
            </div>
            <x-form.textarea name="biography" label="Biography" :value="$member->biography" rows="6" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="years_experience" label="Years of experience" type="number" :value="$member->years_experience" min="0" max="60" />
                <x-form.field name="sort_order" label="Display order" type="number" :value="$member->sort_order" required min="0" />
            </div>
            <x-admin.list-input name="skills" label="Skills" :items="$member->skills ?? []" :max="20" :maxlength="60" />
        </section>
        <section class="card space-y-4" aria-labelledby="links-heading">
            <h2 id="links-heading" class="h-card">Links</h2>
            @foreach (\App\Models\TeamMember::SOCIAL_NETWORKS as $key => $network)
                <x-form.field name="social_links[{{ $key }}]" :label="$network" type="url" :value="$member->social_links[$key] ?? null" maxlength="2048" />
            @endforeach
        </section>
        @if ($member->exists)
            <x-slot:after>
                <section class="card space-y-4" aria-labelledby="portrait-heading">
                    <h2 id="portrait-heading" class="h-card">Portrait</h2>
                    @if ($member->portrait)
                        <div class="flex items-center gap-4">
                            <img src="{{ $member->portrait->url(480) }}" alt="{{ $member->portrait->alt_text }}" width="96" height="96" class="h-24 w-24 rounded-full object-cover">
                            <form method="POST" action="{{ route('admin.team.portrait.destroy', $member) }}" data-confirm="Remove this portrait?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Remove portrait</button></form>
                        </div>
                    @else
                        <p class="text-muted">No portrait. Initials ({{ $member->initials() }}) are shown publicly. Never use stock photos to represent founders.</p>
                    @endif
                    <form method="POST" action="{{ route('admin.team.portrait', $member) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <x-form.field name="portrait" label="Approved portrait (min. 400 × 400 px)" type="file" accept="image/jpeg,image/png,image/webp" required />
                        <x-form.field name="portrait_alt" label="Alternative text" :value="'Portrait of '.$member->name" required maxlength="240" />
                        <x-form.checkbox name="portrait_approved" label="This is an approved photo of this person." required />
                        <button type="submit" class="btn btn-secondary" data-loading-text="Uploading…"><span data-label>Upload portrait</span></button>
                    </form>
                </section>
            </x-slot:after>
            <x-slot:aside>
                <x-admin.publish-panel :item="$member" route-prefix="admin.team" param="teamMember">
                    @if ($member->is_placeholder)
                        @can('approve-placeholder-content')
                            <form method="POST" action="{{ route('admin.team.approve', $member) }}" class="mt-3" data-confirm="Confirm the details on this card are real and owner-approved?">@csrf<button class="btn btn-secondary w-full" type="submit">Mark as owner-approved</button></form>
                        @endcan
                    @endif
                </x-admin.publish-panel>
            </x-slot:aside>
        @endif
    </x-admin.form-layout>
</x-layouts.admin>
