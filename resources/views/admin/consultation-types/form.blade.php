<x-layouts.admin :title="$type->exists ? 'Edit consultation type' : 'New consultation type'">
    <x-admin.page-header :title="$type->exists ? $type->name : 'New consultation type'">
        <x-slot:actions><a href="{{ route('admin.consultation-types.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> All types</a></x-slot:actions>
    </x-admin.page-header>
    <x-admin.form-layout :action="$type->exists ? route('admin.consultation-types.update', $type) : route('admin.consultation-types.store')" :method="$type->exists ? 'PUT' : 'POST'" :model="$type" :submit="$type->isPublished() ? 'Save changes' : 'Save draft'">
        <section class="card space-y-4">
            <x-form.field name="name" label="Name" :value="$type->name" required maxlength="120" />
            <x-form.field name="slug" label="Identifier (slug)" :value="$type->slug" maxlength="140" :readonly="$type->isPublished()" hint="Leave empty to generate from the name." />
            <x-form.textarea name="description" label="Description" :value="$type->description" required maxlength="500" rows="3" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="duration_minutes" label="Duration (minutes)" type="number" :value="$type->duration_minutes" required min="15" max="480" />
                <x-form.field name="sort_order" label="Display order" type="number" :value="$type->sort_order" required min="0" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.select name="pricing_mode" label="Pricing" :options="collect($pricingModes)->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all()" :value="$type->pricing_mode" required />
                <x-form.field name="amount" label="Fee" type="number" step="0.01" min="0" :value="$type->amount" hint="Only for a fixed fee." />
                <x-form.select name="currency" label="Currency" :options="array_combine(config('brivia.currencies'), config('brivia.currencies'))" :value="$type->currency" required />
            </div>
        </section>
        @if ($type->exists)
            <x-slot:aside><x-admin.publish-panel :item="$type" route-prefix="admin.consultation-types" param="consultationType" :preview="false" /></x-slot:aside>
        @endif
    </x-admin.form-layout>
</x-layouts.admin>
