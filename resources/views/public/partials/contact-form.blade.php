<form method="POST" action="{{ $formEnabled ? route('contact.store') : '#' }}" class="mt-8" novalidate>
    @csrf
    @isset($submissionKey)<input type="hidden" name="submission_key" value="{{ $submissionKey }}">@endisset
    <x-form.error-summary title="Please check the form" />
    <div class="hidden" aria-hidden="true"><label for="f-website">Leave this field empty</label><input id="f-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>

    <fieldset class="form-section">
        <legend class="form-section-title float-left w-full">About you</legend>
        <div class="clear-both grid gap-5 md:grid-cols-2">
            <x-form.field name="name" label="Your name" required maxlength="120" autocomplete="name" />
            <x-form.field name="email" label="Email" type="email" required maxlength="254" autocomplete="email" />
            <x-form.field name="phone" label="Phone" type="tel" maxlength="40" autocomplete="tel" hint="Include your country code." />
            <x-form.field name="company" label="Company" maxlength="160" autocomplete="organization" />
        </div>
    </fieldset>

    <fieldset class="form-section mt-8">
        <legend class="form-section-title float-left w-full">Your project</legend>
        <div class="clear-both space-y-5">
            <x-form.select name="enquiry_type" label="What can we help with?" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :value="$selectedService?->slug === 'technical-consulting' ? 'consulting' : 'new_product'" required />

            @if ($services->isNotEmpty() || $packages->isNotEmpty())
                <div class="grid gap-5 md:grid-cols-2">
                    @if ($services->isNotEmpty())
                        <x-form.select name="service" label="Related service" :options="$services->pluck('title', 'slug')->all()" :value="$selectedService?->slug" placeholder="Not sure / none" />
                    @endif
                    @if ($packages->isNotEmpty())
                        <x-form.select name="package" label="Package of interest" :options="$packages->pluck('name', 'slug')->all()" :value="$selectedPackage?->slug" placeholder="Not sure / none" hint="This is not a purchase." />
                    @endif
                </div>
            @endif
            @if ($project)
                <div class="rounded-[12px] border border-line bg-canvas p-4">
                    <x-form.checkbox name="project" :value="$project->slug" :checked="true" :label="'This enquiry relates to: '.$project->title" />
                </div>
            @endif

            @php($site = \App\Support\PublicSite::settings())
            @if ($site->budget_options || $site->timeline_options)
                <div class="grid gap-5 md:grid-cols-2">
                    @if ($site->budget_options)
                        <x-form.select name="budget" label="Budget range" :options="array_combine($site->budget_options, $site->budget_options)" placeholder="Prefer not to say" />
                    @endif
                    @if ($site->timeline_options)
                        <x-form.select name="timeline" label="Timeline" :options="array_combine($site->timeline_options, $site->timeline_options)" placeholder="Not sure yet" />
                    @endif
                </div>
            @endif

            <x-form.textarea name="message" label="Your message" required rows="7" minlength="20" maxlength="5000" hint="20–5000 characters. Describe your goals and any constraints. Never include passwords or access details." />
        </div>
    </fieldset>

    <div class="form-section mt-8 space-y-6">
        <div>
            <x-form.checkbox name="privacy" required :label="'I have read the privacy notice and agree that BRIVIA may use these details to respond to my enquiry.'" />
            @if (\App\Support\PublicSite::legalLinks()['privacy'] ?? false)
                <p class="form-hint ml-8"><a class="btn-link" href="{{ route('privacy') }}" target="_blank">Read the privacy notice<span class="sr-only"> (opens in a new tab)</span></a></p>
            @endif
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto" data-loading-text="Sending…" @disabled(! $formEnabled)><span data-label>Send enquiry</span> <x-icon name="arrow-right" size="18" /></button>
    </div>
</form>
