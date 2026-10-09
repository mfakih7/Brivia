<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\PublicContentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** List helpers shared by admin resource controllers: safe search, status filter, reordering, stale-edit guard. */
trait ReordersRecords
{
    /** Validates that every submitted ID exists, then updates sort order atomically. */
    protected function reorder(Request $request, string $modelClass): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'max:200'],
            'order.*' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $ids = array_map('intval', array_keys($data['order']));

        if ($modelClass::whereIn('id', $ids)->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['order' => 'The list changed while you were editing. Reload the page and try again.']);
        }

        DB::transaction(function () use ($data, $modelClass) {
            foreach ($data['order'] as $id => $position) {
                $modelClass::whereKey((int) $id)->update(['sort_order' => (int) $position]);
            }
        });

        PublicContentCache::flush();

        return back()->with('status', 'Order saved.');
    }

    /** Bounded, wildcard-escaped LIKE search that behaves the same on MySQL and SQLite. */
    protected function applySearch(Builder $query, array $columns, ?string $term): Builder
    {
        $term = trim(mb_substr((string) $term, 0, 100));

        if ($term === '') {
            return $query;
        }

        $escaped = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        return $query->where(function (Builder $q) use ($columns, $escaped) {
            foreach ($columns as $column) {
                $q->orWhereRaw($q->getGrammar()->wrap($column)." LIKE ? ESCAPE '!'", [$escaped]);
            }
        });
    }

    protected function statusFilter(Request $request, array $allowed): ?string
    {
        $status = $request->query('status');

        return is_string($status) && in_array($status, $allowed, true) ? $status : null;
    }

    /** Optimistic concurrency: reject a save if the record changed since the form was loaded. */
    protected function guardStale(Request $request, Model $model): void
    {
        $loaded = (string) $request->input('loaded_version');

        if ($model->exists && $loaded !== '' && $loaded !== (string) $model->updated_at?->getTimestamp()) {
            throw ValidationException::withMessages([
                'loaded_version' => 'Someone else saved this item after you opened it. Reload the page to see their changes, then reapply yours.',
            ]);
        }
    }
}
