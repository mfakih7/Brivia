<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesPublication;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\TeamMember;
use App\Support\AuditLogger;
use App\Support\ImageProcessor;
use App\Support\PreviewRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TeamMemberController extends Controller
{
    use ManagesPublication, ReordersRecords;

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $query = TeamMember::query()->with('portrait');
        $this->applySearch($query, ['name', 'role_title'], $request->query('q'));

        return view('admin.team.index', [
            'members' => $query->when($this->statusFilter($request, ['draft', 'published']), fn ($q, $s) => $q->where('status', $s))
                ->ordered()->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.team.form', ['member' => new TeamMember(['sort_order' => (TeamMember::max('sort_order') ?? 0) + 10])]);
    }

    public function store(TeamMemberRequest $request): RedirectResponse
    {
        $member = TeamMember::create($this->payload($request));
        $this->audit->record('content.created', 'team_member', $member->id);

        return redirect()->route('admin.team.edit', $member)->with('status', 'Draft saved.');
    }

    public function edit(TeamMember $teamMember): View
    {
        return view('admin.team.form', ['member' => $teamMember->load('portrait')]);
    }

    public function update(TeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $this->guardStale($request, $teamMember);
        $teamMember->fill($this->payload($request))->save();
        $this->audit->recordModelChanges('content.updated', 'team_member', $teamMember);

        return back()->with('status', $teamMember->isPublished() ? 'Changes saved and live.' : 'Draft saved.');
    }

    public function destroy(TeamMember $teamMember): RedirectResponse
    {
        $teamMember->delete();
        $this->audit->record('content.deleted', 'team_member', $teamMember->id);

        return redirect()->route('admin.team.index')->with('status', 'Team member deleted.');
    }

    public function uploadPortrait(Request $request, TeamMember $teamMember, ImageProcessor $images): RedirectResponse
    {
        $data = $request->validate([
            'portrait' => ['required', 'file', 'max:5120'],
            'portrait_alt' => ['required', 'string', 'max:240'],
            'portrait_approved' => ['accepted'],
        ], [
            'portrait.max' => 'Images must be 5 MB or smaller.',
            'portrait_approved.accepted' => 'Confirm this is an approved photo of this person.',
        ]);

        $media = $images->store($data['portrait'], 'portrait', $data['portrait_alt'], 'portrait');
        $teamMember->forceFill(['portrait_media_id' => $media->id])->save();
        $this->audit->record('content.media_replaced', 'team_member', $teamMember->id, ['role' => 'portrait']);

        return back()->with('status', 'Portrait saved.');
    }

    public function removePortrait(TeamMember $teamMember): RedirectResponse
    {
        $teamMember->forceFill(['portrait_media_id' => null])->save();
        $this->audit->record('content.media_removed', 'team_member', $teamMember->id, ['role' => 'portrait']);

        return back()->with('status', 'Portrait removed. Initials are shown instead.');
    }

    public function approvePlaceholder(TeamMember $teamMember): RedirectResponse
    {
        Gate::authorize('approve-placeholder-content');
        $teamMember->forceFill(['is_placeholder' => false])->save();
        $this->audit->record('content.placeholder_approved', 'team_member', $teamMember->id, ['is_placeholder' => false]);

        return back()->with('status', 'Marked as owner-approved content.');
    }

    public function publish(TeamMember $teamMember): RedirectResponse
    {
        return $this->publishModel($teamMember);
    }

    public function unpublish(TeamMember $teamMember): RedirectResponse
    {
        return $this->unpublishModel($teamMember);
    }

    public function preview(TeamMember $teamMember, PreviewRenderer $renderer): Response
    {
        return $renderer->render($teamMember->load('portrait'), 'team_member');
    }

    public function sort(Request $request): RedirectResponse
    {
        return $this->reorder($request, TeamMember::class);
    }

    protected function publicationProblems(Model $model): array
    {
        return array_values(array_filter([
            blank($model->biography) ? 'Add a biography.' : null,
            $this->placeholderProblem($model),
        ]));
    }

    protected function auditType(): string
    {
        return 'team_member';
    }

    private function payload(TeamMemberRequest $request): array
    {
        $data = $request->validated();
        $data['social_links'] = array_filter($data['social_links'] ?? [], fn ($url) => filled($url));

        return $data;
    }
}
