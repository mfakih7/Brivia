@props(['heading' => 'Ready to bring your idea to life?', 'body' => "Let's talk about your project and find the best way forward."])
<section class="on-dark relative overflow-hidden bg-navy-900 text-white" aria-labelledby="final-cta">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(40rem_20rem_at_0%_100%,rgba(0,203,234,0.12),transparent_70%)]" aria-hidden="true"></div>
    <div class="container-site relative grid gap-8 py-16 md:py-20 lg:grid-cols-12 lg:items-center">
        <div class="lg:col-span-7">
            <h2 id="final-cta" class="h-section">{{ $heading }}</h2>
            @if ($body)<p class="lead mt-4 max-w-xl text-on-dark-muted">{{ $body }}</p>@endif
        </div>
        <div class="flex flex-col gap-3 sm:flex-row lg:col-span-5 lg:justify-end">
            <a href="{{ route('consultation') }}" class="btn btn-primary btn-lg">Book a consultation <x-icon name="arrow-right" size="18" /></a>
            <a href="{{ route('contact') }}" class="btn btn-secondary btn-lg">Discuss your project</a>
        </div>
    </div>
</section>
