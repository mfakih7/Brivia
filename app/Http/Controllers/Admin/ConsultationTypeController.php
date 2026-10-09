<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConsultationPricingMode;
use App\Http\Controllers\Admin\Concerns\ManagesPublication;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConsultationTypeRequest;
use App\Models\ConsultationType;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsultationTypeController extends Controller
{
    use ManagesPublication, ReordersRecords;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $query = ConsultationType::query()->withCount('appointments');
        $this->applySearch($query, ['name', 'slug'], $request->query('q'));

        return view('admin.consultation-types.index', [
            'types' => $query->when($this->statusFilter($request, ['draft', 'published']), fn ($q, $s) => $q->where('status', $s))
                ->ordered()->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new ConsultationType(['sort_order' => (ConsultationType::max('sort_order') ?? 0) + 10, 'duration_minutes' => 30]));
    }

    public function store(ConsultationTypeRequest $request): RedirectResponse
    {
        $type = ConsultationType::create($request->validated());
        $this->audit->record('content.created', 'consultation_type', $type->id);

        return redirect()->route('admin.consultation-types.edit', $type)->with('status', 'Draft saved.');
    }

    public function edit(ConsultationType $consultationType): View
    {
        return $this->form($consultationType);
    }

    public function update(ConsultationTypeRequest $request, ConsultationType $consultationType): RedirectResponse
    {
        $this->guardStale($request, $consultationType);
        if ($problem = $this->slugLockProblem($consultationType, $request->validated('slug'))) {
            throw ValidationException::withMessages($problem);
        }

        $consultationType->fill($request->validated())->save();
        $this->audit->recordModelChanges('content.updated', 'consultation_type', $consultationType);

        return back()->with('status', $consultationType->isPublished() ? 'Changes saved and live.' : 'Draft saved.');
    }

    public function destroy(ConsultationType $consultationType): RedirectResponse
    {
        if ($count = $consultationType->appointments()->count()) {
            return back()->with('error', "{$count} appointment requests use this type, so it cannot be deleted. Unpublish it instead.");
        }

        $consultationType->delete();
        $this->audit->record('content.deleted', 'consultation_type', $consultationType->id);

        return redirect()->route('admin.consultation-types.index')->with('status', 'Consultation type deleted.');
    }

    public function publish(ConsultationType $consultationType): RedirectResponse
    {
        return $this->publishModel($consultationType);
    }

    public function unpublish(ConsultationType $consultationType): RedirectResponse
    {
        return $this->unpublishModel($consultationType);
    }

    public function sort(Request $request): RedirectResponse
    {
        return $this->reorder($request, ConsultationType::class);
    }

    protected function publicationProblems(Model $model): array
    {
        return $model->pricing_mode === ConsultationPricingMode::Fixed && $model->amount === null ? ['Enter the fixed fee amount.'] : [];
    }

    protected function auditType(): string
    {
        return 'consultation_type';
    }

    private function form(ConsultationType $type): View
    {
        return view('admin.consultation-types.form', ['type' => $type, 'pricingModes' => ConsultationPricingMode::cases()]);
    }
}
