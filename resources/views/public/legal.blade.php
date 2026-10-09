<x-layouts.public :title="$page->title" :preview="$preview ?? false">
    <x-public.page-header eyebrow="Legal" :title="$page->title" :compact="true" />
    <section class="section">
        <div class="container-site">
            <article class="mx-auto max-w-[720px]">
                <p class="text-meta flex flex-wrap gap-x-4 gap-y-1 border-b border-line pb-6 text-muted">
                    <span>Version {{ $page->version_label }}</span>
                    @if ($page->published_at)<span>Last updated {{ $page->updated_at->format('j F Y') }}</span>@endif
                </p>
                <div class="prose-brivia mt-10 text-[1.0625rem] leading-[1.8]">{{ \App\Support\StructuredText::toHtml($page->body) }}</div>
            </article>
        </div>
    </section>
</x-layouts.public>
