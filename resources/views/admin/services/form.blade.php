<x-layouts.admin :title="$service->exists ? 'Edit service' : 'New service'">
    <x-admin.page-header :title="$service->exists ? $service->title : 'New service'">
        <x-slot:actions><a href="{{ route('admin.services.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> All services</a></x-slot:actions>
    </x-admin.page-header>

    <x-admin.form-layout :action="$service->exists ? route('admin.services.update', $service) : route('admin.services.store')" :method="$service->exists ? 'PUT' : 'POST'" :model="$service" :submit="$service->isPublished() ? 'Save changes' : 'Save draft'">
        <section class="card space-y-4" aria-labelledby="basics-heading">
            <h2 id="basics-heading" class="h-card">Basics</h2>
            <x-form.field name="title" label="Title" :value="$service->title" required maxlength="160" />
            <x-form.field name="slug" label="URL slug" :value="$service->slug" maxlength="180" :hint="$service->isPublished() ? 'Locked while published.' : 'Leave empty to generate from the title. Lowercase letters, numbers and hyphens.'" :readonly="$service->isPublished()" />
            <x-form.textarea name="summary" label="Summary" :value="$service->summary" required maxlength="400" rows="2" hint="Shown on cards. Up to 400 characters." />
            <x-form.textarea name="body" label="Main description" :value="$service->body" rows="8" hint="Plain text. Separate paragraphs with a blank line. Start a line with '## ' for a heading or '- ' for a bullet." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="icon_key" label="Icon" :options="collect($icons)->mapWithKeys(fn ($i) => [$i->value => $i->label()])->all()" :value="$service->icon_key" required />
                <x-form.field name="sort_order" label="Display order" type="number" :value="$service->sort_order" required min="0" max="100000" hint="Lower numbers appear first." />
            </div>
        </section>
        <section class="card space-y-6" aria-labelledby="delivery-heading">
            <h2 id="delivery-heading" class="h-card">Deliverables and engagement</h2>
            <p class="text-meta text-muted">Describe typical work. The public page explains that exact deliverables are confirmed in a proposal.</p>
            <x-admin.list-input name="deliverables" label="Example deliverables" :items="$service->deliverables ?? []" :max="20" />
            <x-admin.list-input name="engagement_steps" label="How the engagement works (steps)" :items="$service->engagement_steps ?? []" :max="12" />
        </section>
        <x-admin.seo-fields :model="$service" />
        @if ($service->exists)
            <x-slot:aside><x-admin.publish-panel :item="$service" route-prefix="admin.services" param="service" /></x-slot:aside>
        @else
            <x-slot:aside><div class="card text-meta text-muted">New items are saved as drafts. You can publish after saving.</div></x-slot:aside>
        @endif
    </x-admin.form-layout>
</x-layouts.admin>
