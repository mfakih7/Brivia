<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Explicit Publish / Unpublish actions. Saving ordinary fields never changes publication state.
 */
trait ManagesPublication
{
    /** @return list<string> human-readable reasons publication is blocked */
    abstract protected function publicationProblems(Model $model): array;

    abstract protected function auditType(): string;

    protected function publishModel(Model $model): RedirectResponse
    {
        $problems = $this->publicationProblems($model);

        if ($problems !== []) {
            return back()->with('error', 'This item cannot be published yet.')->with('publication_problems', $problems);
        }

        $model->markPublished();
        app(AuditLogger::class)->record('content.published', $this->auditType(), $model->getKey(), ['status' => 'published', 'published_at' => $model->published_at]);

        return back()->with('status', 'Published. The public website now shows this item.');
    }

    protected function unpublishModel(Model $model): RedirectResponse
    {
        $model->markUnpublished();
        app(AuditLogger::class)->record('content.unpublished', $this->auditType(), $model->getKey(), ['status' => 'draft']);

        return back()->with('status', 'Unpublished. The item is now a draft and hidden from the public website.');
    }

    /** Published slugs are locked so live links never break silently. */
    protected function slugLockProblem(Model $model, ?string $newSlug): ?array
    {
        if ($model->exists && $model->isPublished() && $newSlug !== null && $newSlug !== $model->slug) {
            return ['slug' => 'The slug cannot change while the item is published. Unpublish it first (existing links will stop working).'];
        }

        return null;
    }

    /** Placeholder copy may not go live in production until an owner approves it. */
    protected function placeholderProblem(Model $model): ?string
    {
        if (($model->is_placeholder ?? false) && app()->isProduction()) {
            return 'This is placeholder content. An owner must approve it (or replace it) before it can be published.';
        }

        return null;
    }
}
