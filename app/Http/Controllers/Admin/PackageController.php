<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IconKey;
use App\Enums\PriceMode;
use App\Http\Controllers\Admin\Concerns\ManagesPublication;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackageRequest;
use App\Models\Package;
use App\Models\Service;
use App\Support\AuditLogger;
use App\Support\PreviewRenderer;
use App\Support\PublicContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PackageController extends Controller
{
    use ManagesPublication, ReordersRecords;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $query = Package::query();
        $this->applySearch($query, ['name', 'slug'], $request->query('q'));

        return view('admin.packages.index', [
            'packages' => $query->when($this->statusFilter($request, ['draft', 'published']), fn ($q, $s) => $q->where('status', $s))
                ->ordered()->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Package(['sort_order' => (Package::max('sort_order') ?? 0) + 10]));
    }

    public function store(PackageRequest $request): RedirectResponse
    {
        $package = DB::transaction(function () use ($request) {
            $package = Package::create(Arr::except($request->validated(), ['service_ids']));
            $package->services()->sync($request->validated('service_ids', []));

            return $package;
        });
        $this->audit->record('content.created', 'package', $package->id, Arr::only($package->getAttributes(), ['price_mode', 'price_amount', 'currency']));
        PublicContentCache::flush();

        return redirect()->route('admin.packages.edit', $package)->with('status', 'Draft saved. It is not public until you publish it.');
    }

    public function edit(Package $package): View
    {
        return $this->form($package->load('services'));
    }

    public function update(PackageRequest $request, Package $package): RedirectResponse
    {
        $this->guardStale($request, $package);
        if ($problem = $this->slugLockProblem($package, $request->validated('slug'))) {
            throw ValidationException::withMessages($problem);
        }

        DB::transaction(function () use ($request, $package) {
            $package->fill(Arr::except($request->validated(), ['service_ids']))->save();
            $package->services()->sync($request->validated('service_ids', []));
        });
        $this->audit->recordModelChanges('content.updated', 'package', $package);
        PublicContentCache::flush();

        return back()->with('status', $package->isPublished() ? 'Changes saved and live.' : 'Draft saved.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        if ($count = $package->enquiries()->count()) {
            return back()->with('error', "{$count} enquiries reference this package, so it cannot be deleted. Unpublish it instead.");
        }

        $package->delete();
        $this->audit->record('content.deleted', 'package', $package->id);

        return redirect()->route('admin.packages.index')->with('status', 'Package deleted.');
    }

    public function publish(Package $package): RedirectResponse
    {
        return $this->publishModel($package);
    }

    public function unpublish(Package $package): RedirectResponse
    {
        return $this->unpublishModel($package);
    }

    public function preview(Package $package, PreviewRenderer $renderer): Response
    {
        return $renderer->render($package, 'package');
    }

    public function sort(Request $request): RedirectResponse
    {
        return $this->reorder($request, Package::class);
    }

    protected function publicationProblems(Model $model): array
    {
        return array_values(array_filter([
            blank($model->summary) ? 'Add a summary.' : null,
            empty($model->features) ? 'Add at least one included feature.' : null,
            $model->price_mode->requiresAmount() && $model->price_amount === null ? 'Enter an amount for this pricing mode, or switch to "Request a quote".' : null,
        ]));
    }

    protected function auditType(): string
    {
        return 'package';
    }

    private function form(Package $package): View
    {
        return view('admin.packages.form', [
            'package' => $package,
            'icons' => IconKey::cases(),
            'priceModes' => PriceMode::cases(),
            'services' => Service::ordered()->get(['id', 'title', 'status']),
        ]);
    }
}
