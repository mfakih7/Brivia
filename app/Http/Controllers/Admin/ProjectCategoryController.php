<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectCategory;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectCategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.projects.categories', ['categories' => ProjectCategory::withCount('projects')->ordered()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = ProjectCategory::create($this->validated($request));
        $this->audit->record('content.created', 'project_category', $category->id);

        return back()->with('status', 'Category added.');
    }

    public function update(Request $request, ProjectCategory $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        if ($data['slug'] !== $category->slug && $category->projects()->published()->exists()) {
            return back()->with('error', 'This category has published projects, so its slug (used in filter links) cannot change. You can still rename it.');
        }

        $category->fill($data)->save();
        $this->audit->recordModelChanges('content.updated', 'project_category', $category);

        return back()->with('status', 'Category saved.');
    }

    public function destroy(ProjectCategory $category): RedirectResponse
    {
        if ($count = $category->projects()->count()) {
            return back()->with('error', "{$count} projects use this category. Move them to another category first.");
        }

        $category->delete();
        $this->audit->record('content.deleted', 'project_category', $category->id);

        return back()->with('status', 'Category deleted.');
    }

    private function validated(Request $request, ?ProjectCategory $category = null): array
    {
        $request->merge(['slug' => Str::slug((string) ($request->input('slug') ?: $request->input('name')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('project_categories', 'slug')->ignore($category?->id)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
    }
}
