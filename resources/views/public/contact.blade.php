<x-layouts.public title="Contact" description="Tell us about your project. We review every enquiry and get back to you.">
    <x-public.page-header eyebrow="Contact" title="Discuss your project" intro="Tell us what you are planning or what you would like to improve. We review every enquiry personally and reply by email." />

    <section class="section">
        <div class="container-site grid gap-12 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-8 lg:order-2">
                <div class="rounded-[20px] border border-line bg-surface p-6 md:p-10">
                    <h2 class="h-section !text-[1.75rem]">Send an enquiry</h2>
                    <p class="mt-2 text-muted">Fields marked <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">with an asterisk</span> are required.</p>
                    @if (session('enquiry_reference'))
                        <div class="alert alert-success mt-6" role="status" tabindex="-1" data-error-summary>
                            <p class="font-semibold">Thanks—your enquiry has been received. Our team will review it and get back to you.</p>
                            <p class="mt-1 text-sm">Your reference: <strong>{{ session('enquiry_reference') }}</strong></p>
                        </div>
                    @endif
                    @if (! $formEnabled)
                        <div class="alert alert-warning mt-6" role="status">Online submission is not enabled in this review build yet. @if ($settings->email)Please email <a class="underline" href="mailto:{{ $settings->email }}">{{ $settings->email }}</a> instead.@endif</div>
                    @endif
                    @include('public.partials.contact-form')
                </div>
            </div>

            <aside class="space-y-6 lg:order-1 lg:col-span-4">
                <div class="on-dark rounded-[20px] bg-navy-950 p-6 text-white md:p-8">
                    <h2 class="h-card">What happens next</h2>
                    <ol class="mt-6 space-y-5">
                        @foreach (['We review your message.', 'We reply by email, usually with a few questions or a suggested call.', 'If it is a good fit, we prepare a written proposal.'] as $step)
                            <li class="flex gap-4"><span class="index-num pt-0.5" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="text-on-dark-muted">{{ $step }}</span></li>
                        @endforeach
                    </ol>
                    <p class="text-meta mt-6 border-t border-white/10 pt-5 text-on-dark-subtle">Please don't include passwords, access keys or other confidential credentials.</p>
                </div>
                <section class="rounded-[20px] border border-line p-5 md:p-6" aria-labelledby="reach-us">
                    <h2 id="reach-us" class="font-display text-[1.0625rem] font-bold tracking-[-0.01em]">Other ways to reach us</h2>
                    <div class="mt-4 grid grid-cols-2 gap-2.5">
                        @if ($settings->email)
                            <x-public.contact-tile icon="mail" label="Email" :value="$settings->email" :href="'mailto:'.$settings->email" />
                        @endif
                        @if ($settings->phone)
                            <x-public.contact-tile icon="phone" label="Phone" :value="$settings->phone" :href="'tel:'.preg_replace('/[^0-9+]/', '', $settings->phone)" />
                        @endif
                        @if ($settings->whatsapp_url)
                            <x-public.contact-tile icon="whatsapp" label="WhatsApp" value="Chat with us" :href="$settings->whatsapp_url" :external="true" />
                        @endif
                        <x-public.contact-tile icon="calendar" label="Consultation" value="Request a time" :href="route('consultation')" />
                        @if ($settings->address)
                            <x-public.contact-tile icon="map-pin" label="Location" :value="$settings->address" :wide="true" />
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </section>
</x-layouts.public>
