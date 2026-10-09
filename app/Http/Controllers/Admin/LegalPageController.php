<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesPublication;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LegalPageRequest;
use App\Models\LegalPage;
use App\Support\AuditLogger;
use App\Support\PreviewRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LegalPageController extends Controller
{
    use ManagesPublication, ReordersRecords;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.legal.index', ['pages' => LegalPage::orderBy('key')->get()]);
    }

    public function edit(LegalPage $legalPage): View
    {
        return view('admin.legal.form', ['page' => $legalPage]);
    }

    public function update(LegalPageRequest $request, LegalPage $legalPage): RedirectResponse
    {
        $this->guardStale($request, $legalPage);
        $data = $request->validated();

        // Accepted policy versions must stay meaningful: published text changes need a new version label.
        if ($legalPage->isPublished() && $data['body'] !== $legalPage->body && $data['version_label'] === $legalPage->version_label) {
            throw ValidationException::withMessages(['version_label' => 'You changed published text. Enter a new version label so earlier consent records stay accurate.']);
        }

        $legalPage->fill($data)->save();
        $this->audit->recordModelChanges('content.updated', 'legal_page', $legalPage);

        return back()->with('status', 'Legal page saved.');
    }

    public function publish(LegalPage $legalPage): RedirectResponse
    {
        return $this->publishModel($legalPage);
    }

    public function unpublish(LegalPage $legalPage): RedirectResponse
    {
        return $this->unpublishModel($legalPage);
    }

    public function approvePlaceholder(LegalPage $legalPage): RedirectResponse
    {
        Gate::authorize('approve-placeholder-content');
        $legalPage->forceFill(['is_placeholder' => false])->save();
        $this->audit->record('content.placeholder_approved', 'legal_page', $legalPage->id, ['is_placeholder' => false, 'version_label' => $legalPage->version_label]);

        return back()->with('status', 'Marked as owner-approved text.');
    }

    public function preview(LegalPage $legalPage, PreviewRenderer $renderer): Response
    {
        return $renderer->render($legalPage, 'legal_page');
    }

    protected function publicationProblems(Model $model): array
    {
        return array_values(array_filter([$this->placeholderProblem($model)]));
    }

    protected function auditType(): string
    {
        return 'legal_page';
    }
}
