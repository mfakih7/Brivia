<x-layouts.public title="Packages" description="Starting points for business websites, MVPs and custom applications, modernization and consulting.">
    <x-public.page-header eyebrow="Packages" title="Choose your starting point" intro="Packages describe common starting points. Every project is different, so custom scope, timeline and price are confirmed through a written proposal. Selecting a package is never a purchase." />

    <section class="section" aria-labelledby="packages-list">
        <div class="container-site">
            <h2 id="packages-list" class="sr-only">Available packages</h2>
            @if ($packages->isEmpty())
                <div class="rounded-[18px] border border-line p-10 text-center text-muted">Package details are being finalised. <a class="btn-link" href="{{ route('contact') }}">Request a tailored quote</a>.</div>
            @else
                {{-- Balanced grid: 2×2 for even counts, three columns otherwise. --}}
                <div class="grid gap-5 md:grid-cols-2 {{ $packages->count() % 2 === 0 ? 'lg:mx-auto lg:max-w-[64rem]' : 'lg:grid-cols-3' }}">
                    @foreach ($packages as $package)<x-public.package-card :package="$package" />@endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section-tight bg-canvas" aria-labelledby="custom-scope">
        <div class="container-site grid gap-8 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-7">
                <p class="eyebrow">Custom scope</p>
                <h2 id="custom-scope" class="h-section mt-4">Need something different?</h2>
                <p class="lead mt-4 text-muted">We regularly shape custom scopes. Hosting, paid third-party services and ongoing maintenance are included only when explicitly agreed.</p>
            </div>
            <div class="lg:col-span-5 lg:text-right">
                <a href="{{ route('contact') }}" class="btn btn-primary btn-lg">Discuss custom scope <x-icon name="arrow-right" size="18" /></a>
            </div>
        </div>
    </section>

    <x-public.cta-strip />
</x-layouts.public>
