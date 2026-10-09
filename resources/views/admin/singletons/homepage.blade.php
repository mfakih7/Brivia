<x-layouts.admin title="Homepage">
    <x-admin.page-header title="Homepage" description="Hero, section headings, delivery process and section visibility. Changes are live as soon as they are saved." />
    <x-admin.form-layout :action="route('admin.homepage.update')" method="PUT" :model="$home" submit="Save changes">
        <section class="card space-y-4" aria-labelledby="hero-heading">
            <h2 id="hero-heading" class="h-card">Hero</h2>
            <x-form.field name="hero_eyebrow" label="Eyebrow" :value="$home->hero_eyebrow" maxlength="80" />
            <x-form.field name="hero_headline" label="Headline" :value="$home->hero_headline" required maxlength="160" />
            <x-form.textarea name="hero_subtitle" label="Subtitle" :value="$home->hero_subtitle" rows="2" maxlength="300" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="primary_cta_label" label="Primary button label" :value="$home->primary_cta_label" required maxlength="40" />
                <x-form.select name="primary_cta_route" label="Primary button goes to" :options="$routes" :value="$home->primary_cta_route" required />
                <x-form.field name="secondary_cta_label" label="Secondary button label" :value="$home->secondary_cta_label" maxlength="40" />
                <x-form.select name="secondary_cta_route" label="Secondary button goes to" :options="$routes" :value="$home->secondary_cta_route" placeholder="No secondary button" />
            </div>
            <x-admin.list-input name="credibility_items" label="Credibility line items" :items="$home->credibility_items ?? []" :max="5" :maxlength="60" hint="Plain statements only, e.g. 'Senior development'. No client logos, metrics or awards." />
        </section>
        <section class="card space-y-4" aria-labelledby="sections-heading">
            <h2 id="sections-heading" class="h-card">Section headings</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="services_heading" label="Services" :value="$home->services_heading" maxlength="160" />
                <x-form.field name="projects_heading" label="Projects" :value="$home->projects_heading" maxlength="160" />
                <x-form.field name="packages_heading" label="Packages" :value="$home->packages_heading" maxlength="160" />
                <x-form.field name="about_heading" label="About teaser" :value="$home->about_heading" maxlength="160" />
                <x-form.field name="process_heading" label="Delivery process" :value="$home->process_heading" maxlength="160" />
            </div>
            <x-form.textarea name="about_body" label="About teaser text" :value="$home->about_body" rows="4" maxlength="2000" />
            <x-form.field name="final_cta_heading" label="Final call-to-action heading" :value="$home->final_cta_heading" maxlength="160" />
            <x-form.field name="final_cta_body" label="Final call-to-action text" :value="$home->final_cta_body" maxlength="300" />
        </section>
        <section class="card">
            <x-admin.pair-input name="process_steps" label="Delivery process steps" :items="$home->process_steps ?? []" :max="8" title-label="Step" body-label="Short explanation" />
        </section>
        <section class="card" aria-labelledby="vis-heading">
            <fieldset>
                <legend id="vis-heading" class="h-card">Visible sections</legend>
                <p class="text-meta mb-3 text-muted">Sections with no published content are hidden automatically.</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($sections as $key => $label)
                        <x-form.checkbox name="section_visibility[{{ $key }}]" :label="$label" :checked="$home->showsSection($key)" />
                    @endforeach
                </div>
            </fieldset>
        </section>
    </x-admin.form-layout>
</x-layouts.admin>
