<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IconKey;
use App\Http\Controllers\Admin\Concerns\ManagesPublication;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Support\AuditLogger;
use App\Support\PreviewRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    use ManagesPublication, ReordersRecords;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $query = Service::query()->withCount(['packages', 'projects']);
        $this->applySearch($query, ['title', 'slug'], $request->query('q'));

        return view('admin.services.index', [
            'services' => $query->when($this->statusFilter($request, ['draft', 'published']), fn ($q, $s) => $q->where('status', $s))
                ->ordered()->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service(['sort_order' => (Service::max('sort_order') ?? 0) + 10]), 'icons' => IconKey::cases()]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->validated());
        $this->audit->record('content.created', 'service', $service->id);

        return redirect()->route('admin.services.edit', $service)->with('status', 'Draft saved. It is not public until you publish it.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', ['service' => $service, 'icons' => IconKey::cases()]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $this->guardStale($request, $service);
        if ($problem = $this->slugLockProblem($service, $request->validated('slug'))) {
            throw ValidationException::withMessages($problem);
        }

        $service->fill($request->validated())->save();
        $this->audit->recordModelChanges('content.updated', 'service', $service);

        return back()->with('status', $service->isPublished() ? 'Changes saved and live.' : 'Draft saved.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $uses = array_filter([
            'packages' => $service->packages()->count(),
            'projects' => $service->projects()->count(),
            'enquiries' => $service->enquiries()->count(),
        ]);

        if ($uses !== []) {
            $list = collect($uses)->map(fn ($n, $what) => "{$n} {$what}")->implode(', ');

            return back()->with('error', "This service is used by {$list}, so it cannot be deleted. Unpublish it instead to hide it from the website.");
        }

        $service->delete();
        $this->audit->record('content.deleted', 'service', $service->id);

        return redirect()->route('admin.services.index')->with('status', 'Service deleted.');
    }

    public function publish(Service $service): RedirectResponse
    {
        return $this->publishModel($service);
    }

    public function unpublish(Service $service): RedirectResponse
    {
        return $this->unpublishModel($service);
    }

    public function preview(Service $service, PreviewRenderer $renderer): Response
    {
        return $renderer->render($service, 'service');
    }

    public function sort(Request $request): RedirectResponse
    {
        return $this->reorder($request, Service::class);
    }

    protected function publicationProblems(Model $model): array
    {
        return array_values(array_filter([
            blank($model->summary) ? 'Add a summary.' : null,
            blank($model->body) ? 'Add the main description.' : null,
            empty($model->deliverables) ? 'Add at least one deliverable.' : null,
        ]));
    }

    protected function auditType(): string
    {
        return 'service';
    }
}
