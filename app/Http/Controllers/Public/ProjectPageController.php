<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectPageController extends Controller
{
    public const PER_PAGE = 9;

    public function index(Request $request): View
    {
        // Only categories that contain published projects are offered (and accepted) as filters.
        $categories = ProjectCategory::query()->whereHas('projects', fn ($q) => $q->published())->ordered()->get();

        $requested = $request->query('category');
        $active = is_string($requested) ? $categories->firstWhere('slug', $requested) : null;

        abort_if($requested !== null && ! $active, 404);

        $projects = Project::published()
            ->with(['category', 'cover.media'])
            ->when($active, fn ($q) => $q->where('category_id', $active->id))
            ->newestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        abort_if($projects->currentPage() > 1 && $projects->isEmpty(), 404);

        return view('public.projects.index', compact('projects', 'categories', 'active'));
    }

    public function show(string $slug): View
    {
        $project = Project::published()->where('slug', $slug)->first();
        abort_unless($project, 404);

        return view('public.projects.show', self::viewData($project));
    }

    public static function viewData(Project $project): array
    {
        $project->loadMissing(['category', 'cover.media', 'gallery.media', 'services' => fn ($q) => $q->published()->ordered()]);

        $related = Project::published()->with(['category', 'cover.media'])
            ->whereKeyNot($project->id)
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$project->category_id])
            ->newestFirst()->limit(2)->get();

        return ['project' => $project, 'related' => $related];
    }
}
