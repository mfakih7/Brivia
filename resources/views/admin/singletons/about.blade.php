<x-layouts.admin title="About">
    <x-admin.page-header title="About" description="Company story, mission and values shown on the About page. Changes are live as soon as they are saved." />
    <x-admin.form-layout :action="route('admin.about.update')" method="PUT" :model="$profile" submit="Save changes">
        <section class="card space-y-4">
            <x-form.textarea name="public_intro" label="Introduction" :value="$profile->public_intro" rows="3" maxlength="1000" />
            <x-form.textarea name="story" label="Our story" :value="$profile->story" rows="8" />
            <x-form.textarea name="mission" label="Mission" :value="$profile->mission" rows="3" maxlength="1000" />
        </section>
        <section class="card">
            <x-admin.pair-input name="values" label="Values" :items="$profile->values ?? []" :max="8" />
        </section>
    </x-admin.form-layout>
</x-layouts.admin>
