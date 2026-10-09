<x-layouts.admin title="Settings">
    <x-admin.page-header title="Public settings" description="Public contact details, social links, form choices and default SEO. Secrets and notification recipients are configured on the server, not here." />
    <x-admin.form-layout :action="route('admin.settings.update')" method="PUT" :model="$settings" submit="Save changes">
        <section class="card space-y-4" aria-labelledby="biz-heading">
            <h2 id="biz-heading" class="h-card">Business</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="business_name" label="Business name" :value="$settings->business_name" required maxlength="120" />
                <x-form.field name="copyright_name" label="Copyright name" :value="$settings->copyright_name" maxlength="120" />
            </div>
            <x-form.field name="tagline" label="Tagline" :value="$settings->tagline" maxlength="160" />
        </section>
        <section class="card space-y-4" aria-labelledby="contact-heading">
            <h2 id="contact-heading" class="h-card">Public contact details</h2>
            <p class="text-meta text-muted">Empty values are simply not shown on the website.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field name="email" label="Public email" type="email" :value="$settings->email" maxlength="254" />
                <x-form.field name="phone" label="Public phone" type="tel" :value="$settings->phone" maxlength="40" hint="International format, e.g. +961 …" />
            </div>
            <x-form.field name="whatsapp_url" label="WhatsApp link" type="url" :value="$settings->whatsapp_url" maxlength="2048" hint="https://wa.me/<number>" />
            <x-form.field name="address" label="Address" :value="$settings->address" maxlength="300" />
            <x-form.field name="service_coverage" label="Service coverage" :value="$settings->service_coverage" maxlength="300" hint="e.g. Lebanon and international clients (remote)" />
        </section>
        <section class="card space-y-4" aria-labelledby="social-heading">
            <h2 id="social-heading" class="h-card">Social links</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (\App\Models\SiteSetting::SOCIAL_NETWORKS as $key => $network)
                    <x-form.field name="social_links[{{ $key }}]" :label="$network" type="url" :value="$settings->social_links[$key] ?? null" maxlength="2048" />
                @endforeach
            </div>
        </section>
        <section class="card space-y-6" aria-labelledby="choices-heading">
            <h2 id="choices-heading" class="h-card">Contact form choices</h2>
            <p class="text-meta text-muted">Optional choices visitors can pick. They describe the enquiry; they are not quotes or promises.</p>
            <x-admin.list-input name="budget_options" label="Budget ranges" :items="$settings->budget_options ?? []" :max="10" :maxlength="120" />
            <x-admin.list-input name="timeline_options" label="Timeline choices" :items="$settings->timeline_options ?? []" :max="10" :maxlength="120" />
        </section>
        <section class="card space-y-4" aria-labelledby="seo-default-heading">
            <h2 id="seo-default-heading" class="h-card">Defaults</h2>
            <x-form.field name="default_meta_title" label="Default meta title" :value="$settings->default_meta_title" maxlength="70" />
            <x-form.textarea name="default_meta_description" label="Default meta description" :value="$settings->default_meta_description" maxlength="160" rows="2" />
            <x-form.select name="default_timezone" label="Default visitor timezone" :options="$timezones" :value="$settings->default_timezone" required hint="Pre-selected on the consultation form when the visitor's own timezone cannot be detected." />
        </section>
    </x-admin.form-layout>
</x-layouts.admin>
