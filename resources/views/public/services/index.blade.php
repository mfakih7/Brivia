<x-layouts.public title="Services" description="New products, redesign and modernization, technical consulting and delivery support from senior developers.">
    <x-public.page-header eyebrow="Services" title="A clear path to your next product" intro="Senior developers who plan, build and improve digital products — from a first idea to a product that keeps getting better." />

    <section class="section" aria-labelledby="services-list">
        <div class="container-site">
            <x-public.section-heading eyebrow="What we do" title="Four ways we can help" id="services-list" intro="Each service describes typical work. Your exact scope, timeline and price are always agreed in a written proposal." />

            @if ($services->isEmpty())
                <div class="mt-12 rounded-[18px] border border-line p-10 text-center text-muted">Service details are being finalised. <a class="btn-link" href="{{ route('contact') }}">Tell us what you need</a>.</div>
            @else
                <div class="mt-12 grid grid-cols-2 gap-3 sm:gap-5 lg:gap-6">
                    @foreach ($services as $service)<x-public.service-card :service="$service" :detailed="true" />@endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($steps)
        <section class="section bg-canvas" aria-labelledby="process-title">
            <div class="container-site">
                <x-public.section-heading eyebrow="Delivery process" title="How an engagement works" id="process-title" intro="Every engagement follows the same transparent rhythm, scaled to your project." />
                <div class="mt-14"><x-public.process-steps :steps="$steps" /></div>
            </div>
        </section>
    @endif

    <x-public.cta-strip heading="Not sure which service fits?" body="Book a consultation and we will help you choose a sensible starting point." />
</x-layouts.public>
