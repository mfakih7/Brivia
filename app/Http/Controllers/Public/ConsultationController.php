<?php

namespace App\Http\Controllers\Public;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\AppointmentRequest;
use App\Models\Appointment;
use App\Models\ConsultationType;
use App\Models\LegalPage;
use App\Support\FormTokens;
use App\Support\LocalTime;
use App\Support\NotificationOutbox;
use App\Support\PublicReference;
use App\Support\PublicSite;
use App\Support\Timezones;
use App\Support\VisitorSubmission;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function show(): View
    {
        return view('public.consultation', [
            'types' => ConsultationType::published()->ordered()->get(),
            'timezones' => Timezones::grouped(),
            'defaultTimezone' => PublicSite::settings()->default_timezone ?: 'Asia/Beirut',
            'formEnabled' => true,
            'submissionKey' => FormTokens::issue('consultation'),
        ]);
    }

    public function store(AppointmentRequest $request, VisitorSubmission $submission, NotificationOutbox $outbox): RedirectResponse
    {
        $data = $request->validated();

        $type = ConsultationType::published()->where('slug', $data['consultation_type'])->first();
        if (! $type) {
            throw ValidationException::withMessages(['consultation_type' => 'Choose a consultation type from the list.']);
        }

        $preferred = $this->validInstant($data['preferred_date'], $data['preferred_time'], $data['timezone'], 'preferred');
        $alternate = filled($data['alternate_date'] ?? null)
            ? $this->validInstant($data['alternate_date'], $data['alternate_time'], $data['timezone'], 'alternate')
            : null;

        if ($alternate && $alternate->equalTo($preferred)) {
            throw ValidationException::withMessages(['alternate_time' => 'The alternative time must be different from your preferred time.']);
        }

        $payload = collect($data)->except(['submission_key', 'privacy'])->all();

        $reference = $submission->handle($request, 'consultation', Appointment::class, $payload, function (string $hash) use ($data, $type, $preferred, $alternate, $outbox) {
            $appointment = new Appointment;
            $appointment->forceFill([
                'public_reference' => PublicReference::generate('BRC'),
                'submission_key' => $data['submission_key'],
                'payload_hash' => $hash,
                'consultation_type_id' => $type->id,
                'type_snapshot' => $type->name,
                'duration_minutes' => $type->duration_minutes,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'summary' => $data['summary'],
                'preferred_at_utc' => $preferred,
                'alternate_at_utc' => $alternate,
                'requested_timezone' => $data['timezone'],
                'status' => AppointmentStatus::Requested,
                'policy_version' => LegalPage::currentPrivacyVersion(),
                'privacy_accepted_at' => now(),
            ])->save();

            $outbox->queue(NotificationKind::AppointmentAcknowledgement, $appointment->email, [
                'reference' => $appointment->public_reference, 'name' => $appointment->name, 'type' => $type->name,
                'duration' => $type->duration_minutes, 'timezone' => $data['timezone'], 'preferred_utc' => $preferred->toIso8601String(),
            ], $appointment, "appointment:{$appointment->id}:ack");
            $outbox->queueStaffAlerts(NotificationKind::AppointmentStaffAlert, [
                'reference' => $appointment->public_reference, 'type' => $type->name, 'timezone' => $data['timezone'],
                'preferred_utc' => $preferred->toIso8601String(), 'admin_url' => route('admin.appointments.show', $appointment),
            ], $appointment, "appointment:{$appointment->id}");

            return $appointment;
        });

        return redirect()->route('consultation')->with('appointment_reference', $reference);
    }

    /** Future, within the booking window, and a real (non-DST-gap, non-ambiguous) local time. */
    private function validInstant(string $date, string $time, string $tz, string $prefix): CarbonImmutable
    {
        $utc = LocalTime::toUtcOrFail($date, $time, $tz, "{$prefix}_time");

        if ($utc->lte(now())) {
            throw ValidationException::withMessages(["{$prefix}_date" => 'Choose a date and time in the future.']);
        }
        if ($utc->gt(now()->addDays(config('brivia.appointments.max_days_ahead')))) {
            throw ValidationException::withMessages(["{$prefix}_date" => 'Choose a time within the next '.config('brivia.appointments.max_days_ahead').' days.']);
        }

        return $utc;
    }
}
