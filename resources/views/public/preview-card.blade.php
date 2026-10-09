<x-layouts.public :title="$package?->name ?? $member?->name" :preview="true">
    <x-public.page-header eyebrow="Preview" :title="$package?->name ?? $member?->name" intro="Shown as it will appear in its section on the public site." :compact="true" />
    <section class="section">
        <div class="container-site max-w-xl">
            @if ($package)<x-public.package-card :package="$package" />@endif
            @if ($member)<x-public.founder-card :member="$member" />@endif
        </div>
    </section>
</x-layouts.public>
