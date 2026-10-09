<form method="POST" action="{{ $formEnabled ? route('consultation.store') : '#' }}" class="mt-8" novalidate>
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
        <legend class="form-section-title float-left w-full">Consultation</legend>
        <div class="clear-both space-y-5">
            <x-form.select name="consultation_type" label="Consultation type" :options="$types->mapWithKeys(fn ($t) => [$t->slug => $t->name.' — '.$t->duration_minutes.' min · '.$t->priceLabel()])->all()" :value="$types->count() === 1 ? $types->first()->slug : null" :placeholder="$types->count() > 1 ? 'Choose a type' : null" required />
            <x-form.select name="timezone" label="Your timezone" :options="$timezones" :value="$defaultTimezone" required data-detect-timezone :data-user-selected="old('timezone') ? 'true' : 'false'" hint="Times below are in this timezone. We detect it from your browser when possible." />
        </div>
    </fieldset>

    <fieldset class="form-section mt-8">
        <legend class="form-section-title float-left w-full">Preferred time</legend>
        <div class="clear-both">
            <div class="grid gap-5 md:grid-cols-2">
                <x-form.field name="preferred_date" label="Date" type="date" required :min="now()->addDay()->toDateString()" :max="now()->addDays(90)->toDateString()" />
                <x-form.field name="preferred_time" label="Time" type="time" required step="900" />
            </div>
            <p class="form-hint">Within the next 90 days. This is a request, not a reservation.</p>
            <div class="mt-6 rounded-[12px] border border-line bg-canvas p-4 md:p-5">
                <p class="text-sm font-semibold">Alternative time <span class="font-normal text-muted">(optional)</span></p>
                <div class="mt-4 grid gap-5 md:grid-cols-2">
                    <x-form.field name="alternate_date" label="Date" type="date" :min="now()->addDay()->toDateString()" :max="now()->addDays(90)->toDateString()" />
                    <x-form.field name="alternate_time" label="Time" type="time" step="900" />
                </div>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section mt-8">
        <legend class="form-section-title float-left w-full">Your project</legend>
        <div class="clear-both">
            <x-form.textarea name="summary" label="What would you like to discuss?" required rows="6" minlength="20" maxlength="5000" hint="20–5000 characters. Never include passwords or access details." />
        </div>
    </fieldset>

    <div class="form-section mt-8 space-y-6">
        <div>
            <x-form.checkbox name="privacy" required label="I have read the privacy notice and agree that BRIVIA may use these details to respond to my request." />
            @if (\App\Support\PublicSite::legalLinks()['privacy'] ?? false)
                <p class="form-hint ml-8"><a class="btn-link" href="{{ route('privacy') }}" target="_blank">Read the privacy notice<span class="sr-only"> (opens in a new tab)</span></a></p>
            @endif
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto" data-loading-text="Sending…" @disabled(! $formEnabled)><span data-label>Request this time</span> <x-icon name="arrow-right" size="18" /></button>
    </div>
</form>
