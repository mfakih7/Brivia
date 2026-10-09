<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaRole;
use App\Enums\WorkOrigin;
use App\Http\Controllers\Admin\Concerns\ManagesPublication;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectMedia;
use App\Models\Service;
use App\Support\AuditLogger;
use App\Support\ImageProcessor;
use App\Support\PreviewRenderer;
use App\Support\PublicContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    use ManagesPublication, ReordersRecords;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $query = Project::query()->with(['category', 'cover.media']);
        $this->applySearch($query, ['title', 'slug', 'client_display_name'], $request->query('q'));
        $category = $request->integer('category') ?: null;

        return view('admin.projects.index', [
            'projects' => $query
                ->when($this->statusFilter($request, ['draft', 'published']), fn ($q, $s) => $q->where('status', $s))
                ->when($category, fn ($q, $c) => $q->where('category_id', $c))
                ->ordered()->paginate(20)->withQueryString(),
            'categories' => ProjectCategory::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Project(['sort_order' => (Project::max('sort_order') ?? 0) + 10, 'work_origin' => WorkOrigin::Brivia]));
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = Project::create(Arr::except($request->validated(), ['service_ids']));
            $project->services()->sync($request->validated('service_ids', []));

            return $project;
        });
        $this->audit->record('content.created', 'project', $project->id, ['permission_confirmed' => $project->permission_confirmed]);

        return redirect()->route('admin.projects.edit', $project)->with('status', 'Draft saved. Add a cover image before publishing.');
    }

    public function edit(Project $project): View
    {
        return $this->form($project->load(['services', 'projectMedia.media']));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->guardStale($request, $project);
        if ($problem = $this->slugLockProblem($project, $request->validated('slug'))) {
            throw ValidationException::withMessages($problem);
        }
        if ($project->isPublished() && ! $request->boolean('permission_confirmed')) {
            throw ValidationException::withMessages(['permission_confirmed' => 'A published project must keep its publication permission. Unpublish it first if permission was withdrawn.']);
        }

        DB::transaction(function () use ($request, $project) {
            $project->fill(Arr::except($request->validated(), ['service_ids']))->save();
            $project->services()->sync($request->validated('service_ids', []));
        });
        $this->audit->recordModelChanges('content.updated', 'project', $project);
        PublicContentCache::flush();

        return back()->with('status', $project->isPublished() ? 'Changes saved and live.' : 'Draft saved.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($count = $project->enquiries()->count()) {
            return back()->with('error', "{$count} enquiries reference this project, so it cannot be deleted. Unpublish it instead.");
        }

        $project->delete();
        $this->audit->record('content.deleted', 'project', $project->id);

        return redirect()->route('admin.projects.index')->with('status', 'Project deleted. Its images will be removed by the scheduled media cleanup.');
    }

    public function uploadCover(Request $request, Project $project, ImageProcessor $images): RedirectResponse
    {
        $data = $request->validate([
            'cover' => ['required', 'file', 'max:5120'],
            'cover_alt' => ['required', 'string', 'max:240'],
        ], ['cover.max' => 'Images must be 5 MB or smaller.']);

        $media = $images->store($data['cover'], 'cover', $data['cover_alt'], 'cover');

        DB::transaction(function () use ($project, $media) {
            ProjectMedia::where('project_id', $project->id)->where('role', MediaRole::Cover->value)->lockForUpdate()->get()->each->delete();
            $link = new ProjectMedia(['sort_order' => 0]);
            $link->forceFill(['project_id' => $project->id, 'media_id' => $media->id, 'role' => MediaRole::Cover])->save();
        });
        $this->audit->record('content.media_replaced', 'project', $project->id, ['role' => 'cover']);

        return back()->with('status', 'Cover image saved.');
    }

    public function uploadGallery(Request $request, Project $project, ImageProcessor $images): RedirectResponse
    {
        $data = $request->validate([
            'gallery_image' => ['required', 'file', 'max:5120'],
            'gallery_alt' => ['required', 'string', 'max:240'],
            'gallery_caption' => ['nullable', 'string', 'max:240'],
        ], ['gallery_image.max' => 'Images must be 5 MB or smaller.']);

        $max = config('brivia.media.max_gallery_images');
        if ($project->gallery()->count() >= $max) {
            throw ValidationException::withMessages(['gallery_image' => "A project can have at most {$max} gallery images."]);
        }

        $media = $images->store($data['gallery_image'], 'gallery', $data['gallery_alt'], 'gallery_image');
        $link = new ProjectMedia(['caption' => $data['gallery_caption'] ?? null, 'sort_order' => ($project->gallery()->max('sort_order') ?? 0) + 10]);
        $link->forceFill(['project_id' => $project->id, 'media_id' => $media->id, 'role' => MediaRole::Gallery])->save();
        $this->audit->record('content.media_added', 'project', $project->id, ['role' => 'gallery']);

        return back()->with('status', 'Gallery image added.');
    }

    public function updateMedia(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'media' => ['required', 'array', 'max:20'],
            'media.*.alt_text' => ['required', 'string', 'max:240'],
            'media.*.caption' => ['nullable', 'string', 'max:240'],
            'media.*.sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ], ['media.*.alt_text.required' => 'Every image needs alternative text.']);

        $links = $project->projectMedia()->with('media')->get()->keyBy('id');
        if (array_diff(array_map('intval', array_keys($data['media'])), $links->keys()->all()) !== []) {
            throw ValidationException::withMessages(['media' => 'One of the images no longer belongs to this project. Reload and try again.']);
        }

        DB::transaction(function () use ($data, $links) {
            foreach ($data['media'] as $id => $values) {
                $link = $links[(int) $id];
                $link->fill(['caption' => $values['caption'] ?? null, 'sort_order' => $values['sort_order']])->save();
                $link->media->fill(['alt_text' => $values['alt_text']])->save();
            }
        });
        PublicContentCache::flush();

        return back()->with('status', 'Image details saved.');
    }

    public function removeMedia(Project $project, ProjectMedia $projectMedia): RedirectResponse
    {
        abort_unless($projectMedia->project_id === $project->id, 404);

        if ($projectMedia->role === MediaRole::Cover && $project->isPublished()) {
            return back()->with('error', 'A published project needs a cover. Upload a replacement cover instead of removing it.');
        }

        $projectMedia->delete();
        $this->audit->record('content.media_removed', 'project', $project->id, ['role' => $projectMedia->role->value]);

        return back()->with('status', 'Image removed from the project.');
    }

    public function publish(Project $project): RedirectResponse
    {
        return $this->publishModel($project->load(['category', 'projectMedia.media']));
    }

    public function unpublish(Project $project): RedirectResponse
    {
        return $this->unpublishModel($project);
    }

    public function preview(Project $project, PreviewRenderer $renderer): Response
    {
        return $renderer->render($project->load(['category', 'services', 'projectMedia.media']), 'project');
    }

    public function sort(Request $request): RedirectResponse
    {
        return $this->reorder($request, Project::class);
    }

    protected function publicationProblems(Model $model): array
    {
        $cover = $model->projectMedia->firstWhere('role', MediaRole::Cover);

        return array_values(array_filter([
            ! $model->permission_confirmed ? 'Confirm that BRIVIA has permission to publish this project.' : null,
            blank($model->summary) ? 'Add a summary.' : null,
            blank($model->solution) && blank($model->approach) ? 'Describe the approach or solution.' : null,
            ! $cover ? 'Upload a cover image.' : null,
            $model->projectMedia->contains(fn ($link) => blank($link->media?->alt_text)) ? 'Every image needs alternative text.' : null,
            $model->work_origin === WorkOrigin::Concept && app()->isProduction() && Gate::denies('approve-placeholder-content')
                ? 'Sample concepts can only be published by an owner in production.' : null,
        ]));
    }

    protected function auditType(): string
    {
        return 'project';
    }

    private function form(Project $project): View
    {
        return view('admin.projects.form', [
            'project' => $project,
            'categories' => ProjectCategory::ordered()->get(),
            'services' => Service::ordered()->get(['id', 'title', 'status']),
            'origins' => WorkOrigin::cases(),
        ]);
    }
}
