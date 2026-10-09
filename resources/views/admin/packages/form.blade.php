<x-layouts.admin :title="$package->exists ? 'Edit package' : 'New package'">
    <x-admin.page-header :title="$package->exists ? $package->name : 'New package'">
        <x-slot:actions><a href="{{ route('admin.packages.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> All packages</a></x-slot:actions>
    </x-admin.page-header>

    <x-admin.form-layout :action="$package->exists ? route('admin.packages.update', $package) : route('admin.packages.store')" :method="$package->exists ? 'PUT' : 'POST'" :model="$package" :submit="$package->isPublished() ? 'Save changes' : 'Save draft'">
        <section class="card space-y-4" aria-labelledby="basics-heading">
            <h2 id="basics-heading" class="h-card">Basics</h2>
            <x-form.field name="name" label="Name" :value="$package->name" required maxlength="160" />
            <x-form.field name="slug" label="Identifier (slug)" :value="$package->slug" maxlength="180" :hint="$package->isPublished() ? 'Locked while published.' : 'Leave empty to generate from the name.'" :readonly="$package->isPublished()" />
            <x-form.textarea name="summary" label="Short description" :value="$package->summary" required maxlength="400" rows="2" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="icon_key" label="Icon" :options="collect($icons)->mapWithKeys(fn ($i) => [$i->value => $i->label()])->all()" :value="$package->icon_key" required />
                <x-form.field name="sort_order" label="Display order" type="number" :value="$package->sort_order" required min="0" max="100000" />
            </div>
            <x-form.checkbox name="is_featured" label="Featured on the homepage preview" :checked="$package->is_featured" />
        </section>
        <section class="card space-y-6" aria-labelledby="scope-heading">
            <h2 id="scope-heading" class="h-card">What's included</h2>
            <x-admin.list-input name="features" label="Included features" :items="$package->features ?? []" :max="20" />
            <x-admin.list-input name="exclusions" label="Not included" :items="$package->exclusions ?? []" :max="20" hint="Be explicit, e.g. hosting, paid third-party services, indefinite maintenance." />
        </section>
        <section class="card space-y-4" aria-labelledby="price-heading">
            <h2 id="price-heading" class="h-card">Pricing</h2>
            <p class="text-meta text-muted">Use "Request a quote" until the owner approves a real price. Quote mode never shows an amount.</p>
            <x-form.select name="price_mode" label="Pricing mode" :options="collect($priceModes)->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all()" :value="$package->price_mode" required />
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.field name="price_amount" label="Amount" type="number" step="0.01" min="0" :value="$package->price_amount" hint="Required for fixed and starting-from." />
                <x-form.select name="currency" label="Currency" :options="array_combine(config('brivia.currencies'), config('brivia.currencies'))" :value="$package->currency" required />
                <x-form.field name="billing_label" label="Billing label" :value="$package->billing_label" maxlength="60" hint="e.g. per project" />
            </div>
        </section>
        <section class="card" aria-labelledby="services-heading">
            <fieldset>
                <legend id="services-heading" class="h-card">Related services</legend>
                @php($selected = collect(old('service_ids', $package->relationLoaded('services') ? $package->services->pluck('id')->all() : []))->map(fn ($id) => (int) $id))
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @forelse ($services as $service)
                        <label class="form-check"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked($selected->contains($service->id))> <span>{{ $service->title }} @if ($service->status->value === 'draft')<span class="chip ml-1">Draft</span>@endif</span></label>
                    @empty
                        <p class="text-muted">No services yet.</p>
                    @endforelse
                </div>
                @error('service_ids')<p class="form-error">{{ $message }}</p>@enderror
            </fieldset>
        </section>
        <x-admin.seo-fields :model="$package" />
        @if ($package->exists)
            <x-slot:aside><x-admin.publish-panel :item="$package" route-prefix="admin.packages" param="package" /></x-slot:aside>
        @else
            <x-slot:aside><div class="card text-meta text-muted">New items are saved as drafts. You can publish after saving.</div></x-slot:aside>
        @endif
    </x-admin.form-layout>
</x-layouts.admin>
