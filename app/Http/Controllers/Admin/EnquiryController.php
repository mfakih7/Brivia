<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\RecordNote;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\EnquiryWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    use ReordersRecords;

    public function index(Request $request): View
    {
        $query = Enquiry::query()->with('assignee:id,name');
        $this->applySearch($query, ['name', 'email', 'company', 'public_reference'], $request->query('q'));

        return view('admin.enquiries.index', [
            'enquiries' => $query
                ->when($this->statusFilter($request, array_column(EnquiryStatus::cases(), 'value')), fn ($q, $s) => $q->where('status', $s))
                ->when(EnquiryType::tryFrom((string) $request->query('type')), fn ($q, $t) => $q->where('enquiry_type', $t->value))
                ->when($request->query('assignee') === 'me', fn ($q) => $q->where('assigned_to', $request->user()->id))
                ->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        return view('admin.enquiries.show', [
            'enquiry' => $enquiry->load(['assignee:id,name', 'notes.author:id,name', 'events.actor:id,name', 'deliveries']),
            'staff' => User::operational()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function status(Request $request, Enquiry $enquiry, EnquiryWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
            'lock_version' => ['required', 'integer'],
        ]);
        $workflow->transition($request->user(), $enquiry, (int) $data['lock_version'], EnquiryStatus::from($data['status']));

        return back()->with('status', 'Status updated.');
    }

    public function assign(Request $request, Enquiry $enquiry, EnquiryWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['assigned_to' => ['nullable', 'integer'], 'lock_version' => ['required', 'integer']]);
        $workflow->assign($request->user(), $enquiry, (int) $data['lock_version'], $data['assigned_to'] ?? null);

        return back()->with('status', 'Assignee updated.');
    }

    public function note(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $note = new RecordNote;
        $note->forceFill(['enquiry_id' => $enquiry->id, 'author_id' => $request->user()->id, 'body' => $data['body']])->save();

        return back()->with('status', 'Internal note added. It is never sent to the client.');
    }

    /** Owner-only, explicit hard deletion of a closed enquiry and its notes, history and delivery records. */
    public function destroy(Request $request, Enquiry $enquiry, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('delete-personal-records');
        $request->validate(['confirm_reference' => ['required', Rule::in([$enquiry->public_reference])]], ['confirm_reference.in' => 'Type the exact reference to confirm deletion.']);

        if (! $enquiry->status->isFinal()) {
            return back()->with('error', 'Only closed or spam enquiries can be deleted.');
        }

        $id = $enquiry->id;
        $enquiry->delete();
        $audit->record('personal_record.deleted', 'enquiry', $id);

        return redirect()->route('admin.enquiries.index')->with('status', 'Enquiry permanently deleted.');
    }
}
