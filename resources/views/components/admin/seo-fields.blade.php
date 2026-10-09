@props(['model'])
<section class="card space-y-4" aria-labelledby="seo-heading">
    <h2 id="seo-heading" class="h-card">Search engines</h2>
    <x-form.field name="meta_title" label="Meta title" :value="$model->meta_title" maxlength="70" hint="Up to 70 characters. Leave empty to use the title." />
    <x-form.textarea name="meta_description" label="Meta description" :value="$model->meta_description" maxlength="160" rows="2" hint="Up to 160 characters. Leave empty to use the summary." />
</section>
