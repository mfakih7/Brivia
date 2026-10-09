<x-layouts.public title="Services" description="New products, redesign and modernization, technical consulting and delivery support from senior developers.">
    <x-public.page-header eyebrow="Services" title="A clear path to your next product" intro="Whether you are starting from an idea or improving an existing product, we help you plan, build and deliver with senior expertise. The examples below describe typical work; your exact scope is always agreed in a proposal." />

    <section class="section" aria-labelledby="services-list">
        <div class="container-site">
            <h2 id="services-list" class="sr-only">Our services</h2>
            @if ($services->isEmpty())
                <div class="rounded-[18px] border border-line p-10 text-center text-muted">Service details are being finalised. <a class="btn-link" href="{{ route('contact') }}">Tell us what you need</a>.</div>
            @else
                <ol class="border-b border-line">
                    @foreach ($services as $service)<x-public.service-row :service="$service" :index="$loop->iteration" :detailed="true" />@endforeach
                </ol>
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
