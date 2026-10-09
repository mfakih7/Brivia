<x-layouts.admin :title="$project->exists ? 'Edit project' : 'New project'">
    <x-admin.page-header :title="$project->exists ? $project->title : 'New project'">
        <x-slot:actions><a href="{{ route('admin.projects.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> All projects</a></x-slot:actions>
    </x-admin.page-header>

    <x-admin.form-layout :action="$project->exists ? route('admin.projects.update', $project) : route('admin.projects.store')" :method="$project->exists ? 'PUT' : 'POST'" :model="$project" :submit="$project->isPublished() ? 'Save changes' : 'Save draft'">
        <section class="card space-y-4" aria-labelledby="basics-heading">
            <h2 id="basics-heading" class="h-card">Basics</h2>
            <x-form.field name="title" label="Title" :value="$project->title" required maxlength="180" />
            <x-form.field name="slug" label="URL slug" :value="$project->slug" maxlength="200" :hint="$project->isPublished() ? 'Locked while published.' : 'Leave empty to generate from the title.'" :readonly="$project->isPublished()" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="category_id" label="Category" :options="$categories->pluck('name', 'id')->all()" :value="$project->category_id" required placeholder="Choose a category" />
                <x-form.select name="work_origin" label="Origin" :options="collect($origins)->mapWithKeys(fn ($o) => [$o->value => $o->label()])->all()" :value="$project->work_origin" required hint="Work done before BRIVIA must be labelled 'Earlier founder experience'. Fictional examples are 'Sample concept'." />
            </div>
            <x-form.textarea name="summary" label="Summary" :value="$project->summary" required maxlength="500" rows="2" />
            <x-form.field name="client_display_name" label="Client display name" :value="$project->client_display_name" maxlength="160" hint="Only if the client authorised being named. Leave empty to keep the client confidential." />
        </section>
        <section class="card space-y-4" aria-labelledby="story-heading">
            <h2 id="story-heading" class="h-card">Case study</h2>
            <x-form.textarea name="problem" label="The problem / context" :value="$project->problem" rows="4" />
            <x-form.textarea name="contribution" label="Our role and contribution" :value="$project->contribution" rows="3" />
            <x-form.textarea name="approach" label="Approach" :value="$project->approach" rows="4" />
            <x-form.textarea name="solution" label="Solution" :value="$project->solution" rows="4" />
            <x-form.textarea name="outcomes" label="Measured outcomes" :value="$project->outcomes" rows="3" hint="Only results that can be supported with evidence. Leave empty otherwise." />
            <x-admin.list-input name="technologies" label="Technologies" :items="$project->technologies ?? []" :max="20" :maxlength="60" />
            <x-form.field name="website_url" label="Live website link" type="url" :value="$project->website_url" maxlength="2048" hint="Only if the client approved linking. http(s) only." />
        </section>
        <section class="card space-y-4" aria-labelledby="pub-heading">
            <h2 id="pub-heading" class="h-card">Permission and placement</h2>
            <x-form.checkbox name="permission_confirmed" label="BRIVIA has permission to publish this project and any named client, images and link." :checked="$project->permission_confirmed" />
            <x-form.checkbox name="is_featured" label="Feature on the homepage" :checked="$project->is_featured" />
            <x-form.field name="sort_order" label="Display order" type="number" :value="$project->sort_order" required min="0" max="100000" />
            <fieldset>
                <legend class="form-label">Related services</legend>
                @php($selected = collect(old('service_ids', $project->relationLoaded('services') ? $project->services->pluck('id')->all() : []))->map(fn ($id) => (int) $id))
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($services as $service)
                        <label class="form-check"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked($selected->contains($service->id))> <span>{{ $service->title }}</span></label>
                    @endforeach
                </div>
            </fieldset>
        </section>
        <x-admin.seo-fields :model="$project" />

        @if ($project->exists)
            <x-slot:after>
                @include('admin.projects.media', ['project' => $project])
            </x-slot:after>
            <x-slot:aside><x-admin.publish-panel :item="$project" route-prefix="admin.projects" param="project" /></x-slot:aside>
        @else
            <x-slot:aside><div class="card text-meta text-muted">Save the draft first, then add a cover and gallery images.</div></x-slot:aside>
        @endif
    </x-admin.form-layout>
</x-layouts.admin>
