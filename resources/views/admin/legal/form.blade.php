<x-layouts.admin :title="$page->title">
    <x-admin.page-header :title="$page->title">
        <x-slot:actions><a href="{{ route('admin.legal.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" size="18" /> Legal pages</a></x-slot:actions>
    </x-admin.page-header>
    @if ($page->is_placeholder)
        <div class="alert alert-warning mb-6" role="status">This is a draft outline, not approved legal text. An owner must review it (with legal counsel where appropriate) and mark it approved before it can be published in production.</div>
    @endif
    <x-admin.form-layout :action="route('admin.legal.update', $page)" method="PUT" :model="$page" :submit="$page->isPublished() ? 'Save changes' : 'Save draft'">
        <section class="card space-y-4">
            <x-form.field name="title" label="Title" :value="$page->title" required maxlength="160" />
            <x-form.field name="version_label" label="Version label" :value="$page->version_label" required maxlength="40" hint="Recorded with every visitor's consent. Change it whenever published text changes." />
            <x-form.textarea name="body" label="Text" :value="$page->body" required rows="20" hint="Plain text. '## ' for headings, '- ' for bullets, blank line between paragraphs." />
        </section>
        <x-slot:aside>
            <x-admin.publish-panel :item="$page" route-prefix="admin.legal" param="legalPage" :deletable="false">
                @if ($page->is_placeholder)
                    @can('approve-placeholder-content')
                        <form method="POST" action="{{ route('admin.legal.approve', $page) }}" class="mt-3" data-confirm="Confirm this text has been reviewed and approved by the owner?">@csrf<button class="btn btn-secondary w-full" type="submit">Mark as owner-approved</button></form>
                    @endcan
                @endif
            </x-admin.publish-panel>
        </x-slot:aside>
    </x-admin.form-layout>
</x-layouts.admin>
