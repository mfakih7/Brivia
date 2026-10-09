<x-layouts.admin title="Legal">
    <x-admin.page-header title="Legal pages" description="Privacy and terms text. Must be owner-approved before publication; never claim regulatory compliance without review." />
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($pages as $page)
            <a href="{{ route('admin.legal.edit', $page) }}" class="card card-link block">
                <p class="h-card">{{ $page->title }}</p>
                <p class="text-meta mt-1 text-muted">Version {{ $page->version_label }} · /{{ $page->key->value }}</p>
                <div class="mt-3 flex flex-wrap gap-2"><x-admin.status-chip :item="$page" /></div>
            </a>
        @endforeach
    </div>
</x-layouts.admin>
