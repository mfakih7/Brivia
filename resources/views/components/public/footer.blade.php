@props(['site', 'legal'])
@php
    $socialIcons = ['linkedin' => 'linkedin', 'github' => 'github', 'instagram' => 'instagram', 'facebook' => 'facebook', 'x' => 'x-social'];
    $socials = $site->filledSocialLinks();
@endphp
<footer class="on-dark bg-navy-950 text-white">
    <div class="container-site border-t border-white/[0.08]">
        <div class="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr] lg:py-16">
            <div>
                <x-brand.lockup :tagline="true" />
                @if ($site->service_coverage)<p class="mt-5 max-w-xs text-sm leading-relaxed text-on-dark-subtle">{{ $site->service_coverage }}</p>@endif
            </div>
            <nav aria-labelledby="footer-explore">
                <h2 id="footer-explore" class="text-xs font-semibold uppercase tracking-[0.16em] text-on-dark-subtle">Explore</h2>
                <ul class="mt-4 space-y-2.5 text-[0.9375rem]">
                    <li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('services.index') }}">Services</a></li>
                    <li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('projects.index') }}">Projects</a></li>
                    <li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('packages') }}">Packages</a></li>
                    <li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('about') }}">About Us</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="footer-work">
                <h2 id="footer-work" class="text-xs font-semibold uppercase tracking-[0.16em] text-on-dark-subtle">Work with us</h2>
                <ul class="mt-4 space-y-2.5 text-[0.9375rem]">
                    <li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('contact') }}">Contact</a></li>
                    <li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('consultation') }}">Book a consultation</a></li>
                    <li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('home') }}">Home</a></li>
                </ul>
            </nav>
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-on-dark-subtle">Get in touch</h2>
                <ul class="mt-4 space-y-2.5 text-[0.9375rem]">
                    @if ($site->email)<li><a class="inline-flex items-center gap-2.5 text-on-dark-muted transition hover:text-white" href="mailto:{{ $site->email }}"><x-icon name="mail" size="17" /> {{ $site->email }}</a></li>@endif
                    @if ($site->phone)<li><a class="inline-flex items-center gap-2.5 text-on-dark-muted transition hover:text-white" href="tel:{{ preg_replace('/[^0-9+]/', '', $site->phone) }}"><x-icon name="phone" size="17" /> {{ $site->phone }}</a></li>@endif
                    @if ($site->whatsapp_url)<li><a class="inline-flex items-center gap-2.5 text-on-dark-muted transition hover:text-white" href="{{ $site->whatsapp_url }}" rel="noopener" target="_blank"><x-icon name="whatsapp" size="17" /> WhatsApp<span class="sr-only"> (opens in a new tab)</span></a></li>@endif
                    @if (! $site->email && ! $site->phone && ! $site->whatsapp_url)<li><a class="text-on-dark-muted transition hover:text-white" href="{{ route('contact') }}">Send us a message</a></li>@endif
                </ul>
                @if ($socials)
                    <ul class="mt-5 flex gap-2" aria-label="Social profiles">
                        @foreach ($socials as $network => $url)
                            <li><a href="{{ $url }}" rel="noopener me" target="_blank" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-on-dark-muted transition hover:border-white/50 hover:text-white"><x-icon :name="$socialIcons[$network] ?? 'website'" size="17" /><span class="sr-only">{{ \App\Models\SiteSetting::SOCIAL_NETWORKS[$network] ?? $network }} (opens in a new tab)</span></a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-white/10 py-6 text-sm text-on-dark-subtle sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $site->copyright_name ?: $site->business_name ?: 'BRIVIA' }}. All rights reserved.</p>
            <ul class="flex gap-6">
                @if ($legal['privacy'] ?? false)<li><a class="transition hover:text-white" href="{{ route('privacy') }}">Privacy</a></li>@endif
                @if ($legal['terms'] ?? false)<li><a class="transition hover:text-white" href="{{ route('terms') }}">Terms</a></li>@endif
            </ul>
        </div>
    </div>
</footer>
