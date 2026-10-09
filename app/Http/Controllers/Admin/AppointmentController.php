<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Admin\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\RecordNote;
use App\Models\User;
use App\Support\AppointmentScheduler;
use App\Support\AuditLogger;
use App\Support\LocalTime;
use App\Support\Timezones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    use ReordersRecords;

    public function __construct(private readonly AppointmentScheduler $scheduler) {}

    public function index(Request $request): View
    {
        $query = Appointment::query()->with('assignee:id,name');
        $this->applySearch($query, ['name', 'email', 'company', 'public_reference'], $request->query('q'));
        $status = $this->statusFilter($request, array_column(AppointmentStatus::cases(), 'value'));

        $query->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('assignee') === 'me', fn ($q) => $q->where('assigned_to', $request->user()->id));

        // Upcoming confirmed appointments read best soonest-first; everything else newest-first.
        $status === AppointmentStatus::Confirmed->value
            ? $query->orderBy('confirmed_start_at_utc')->orderBy('id')
            : $query->latest('id');

        return view('admin.appointments.index', ['appointments' => $query->paginate(20)->withQueryString()]);
    }

    public function show(Appointment $appointment): View
    {
        return view('admin.appointments.show', [
            'appointment' => $appointment->load(['assignee:id,name', 'consultationType:id,name,duration_minutes', 'notes.author:id,name', 'events.actor:id,name', 'deliveries']),
            'staff' => User::operational()->orderBy('name')->get(['id', 'name']),
            'timezones' => Timezones::grouped(),
            'staffTimezone' => config('brivia.appointments.staff_timezone'),
        ]);
    }

    public function confirm(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'lock_version' => ['required', 'integer'],
            'assigned_to' => ['required', 'integer'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'meeting_url' => ['nullable', 'string', 'max:2048', 'url:https,http'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $start = LocalTime::toUtcOrFail($data['start_date'], $data['start_time'], $data['timezone'], 'start_time');
        $rescheduling = $appointment->status === AppointmentStatus::Confirmed;

        $this->scheduler->confirm($request->user(), $appointment, (int) $data['lock_version'], (int) $data['assigned_to'], $start, (int) $data['duration_minutes'], $data['meeting_url'] ?? null, $data['reason'] ?? null);

        return back()->with('status', $rescheduling ? 'Rescheduled. The client email has been queued.' : 'Confirmed. The confirmation email has been queued.');
    }

    public function decline(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['lock_version' => ['required', 'integer'], 'reason' => ['required', 'string', 'max:500']]);
        $this->scheduler->decline($request->user(), $appointment, (int) $data['lock_version'], $data['reason']);

        return back()->with('status', 'Request declined. The client email has been queued.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['lock_version' => ['required', 'integer'], 'reason' => ['required', 'string', 'max:500']]);
        $this->scheduler->cancel($request->user(), $appointment, (int) $data['lock_version'], $data['reason']);

        return back()->with('status', 'Appointment cancelled. Pending reminders were stopped and the client email has been queued.');
    }

    public function complete(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['lock_version' => ['required', 'integer']]);
        $this->scheduler->complete($request->user(), $appointment, (int) $data['lock_version']);

        return back()->with('status', 'Marked as completed.');
    }

    public function assign(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['assigned_to' => ['nullable', 'integer'], 'lock_version' => ['required', 'integer']]);
        $this->scheduler->assign($request->user(), $appointment, (int) $data['lock_version'], $data['assigned_to'] ?? null);

        return back()->with('status', 'Assignee updated.');
    }

    public function note(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $note = new RecordNote;
        $note->forceFill(['appointment_id' => $appointment->id, 'author_id' => $request->user()->id, 'body' => $data['body']])->save();

        return back()->with('status', 'Internal note added. It is never sent to the client.');
    }

    public function destroy(Request $request, Appointment $appointment, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('delete-personal-records');
        $request->validate(['confirm_reference' => ['required', Rule::in([$appointment->public_reference])]], ['confirm_reference.in' => 'Type the exact reference to confirm deletion.']);

        if (! $appointment->status->isClosed()) {
            return back()->with('error', 'Only completed, cancelled or declined appointments can be deleted.');
        }

        $id = $appointment->id;
        $appointment->delete();
        $audit->record('personal_record.deleted', 'appointment', $id);

        return redirect()->route('admin.appointments.index')->with('status', 'Appointment permanently deleted.');
    }
}
