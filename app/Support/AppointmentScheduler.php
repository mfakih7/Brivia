<?php

namespace App\Support;

use App\Enums\AppointmentStatus;
use App\Enums\NotificationKind;
use App\Exceptions\SchedulingConflict;
use App\Exceptions\StaleRecord;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All appointment state changes. Confirm/reschedule serialise per assignee by locking the
 * assignee user row(s) (sorted by ID) inside a transaction, then checking for overlapping
 * confirmed appointments (existing.start < new.end AND existing.end > new.start; adjacent
 * slots allowed). Every change checks the optimistic lock_version to reject stale edits.
 */
class AppointmentScheduler
{
    public function __construct(private readonly NotificationOutbox $outbox, private readonly AuditLogger $audit) {}

    public function confirm(User $actor, Appointment $appointment, int $expectedVersion, int $assigneeId, CarbonImmutable $startUtc, int $minutes, ?string $meetingUrl, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $expectedVersion, $assigneeId, $startUtc, $minutes, $meetingUrl, $reason) {
            $appointment = $this->lockFresh($appointment, $expectedVersion);
            $rescheduling = $appointment->status === AppointmentStatus::Confirmed;

            if (! in_array($appointment->status, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['status' => 'Only requested or confirmed appointments can be scheduled.']);
            }
            if ($rescheduling && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'Give a short reason for rescheduling. It is included in the email to the client.']);
            }
            if ($startUtc->lte(now())) {
                throw ValidationException::withMessages(['start_date' => 'The confirmed start time must be in the future.']);
            }

            $endUtc = $startUtc->addMinutes($minutes);

            // Lock every involved staff row in a stable order to avoid deadlocks.
            $lockIds = collect([$assigneeId, $appointment->assigned_to])->filter()->unique()->sort()->values();
            $users = User::whereIn('id', $lockIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $assignee = $users->get($assigneeId);

            if (! $assignee || ! $assignee->canManageOperations()) {
                throw ValidationException::withMessages(['assigned_to' => 'Choose an active owner or operations manager.']);
            }

            $this->testingLockDelay();

            $overlap = Appointment::query()
                ->where('assigned_to', $assignee->id)
                ->where('status', AppointmentStatus::Confirmed->value)
                ->whereKeyNot($appointment->id)
                ->where('confirmed_start_at_utc', '<', $endUtc)
                ->where('confirmed_end_at_utc', '>', $startUtc)
                ->first(['id', 'public_reference', 'confirmed_start_at_utc']);

            if ($overlap) {
                throw new SchedulingConflict("{$assignee->name} already has a confirmed appointment ({$overlap->public_reference}) that overlaps this time.");
            }

            $previous = $this->scheduleSnapshot($appointment);
            $appointment->forceFill([
                'status' => AppointmentStatus::Confirmed,
                'assigned_to' => $assignee->id,
                'confirmed_start_at_utc' => $startUtc,
                'confirmed_end_at_utc' => $endUtc,
                'meeting_url' => $meetingUrl ?: null,
                'schedule_version' => $appointment->schedule_version + 1,
                'lock_version' => $appointment->lock_version + 1,
            ])->save();

            $this->event($appointment, $actor, $rescheduling ? 'rescheduled' : 'confirmed', $previous, $reason);
            $this->audit->record($rescheduling ? 'appointment.rescheduled' : 'appointment.confirmed', 'appointment', $appointment->id, [
                'status' => 'confirmed', 'assigned_to' => $assignee->id, 'confirmed_start_at_utc' => $startUtc, 'confirmed_end_at_utc' => $endUtc,
            ], $actor->id);

            $this->outbox->suppressObsolete($appointment, 'Superseded by a later schedule change.');
            $payload = $this->payload($appointment) + ['reason' => $rescheduling ? $reason : null];
            $kind = $rescheduling ? NotificationKind::AppointmentRescheduled : NotificationKind::AppointmentConfirmed;
            $this->outbox->queue($kind, $appointment->email, $payload, $appointment, "appointment:{$appointment->id}:{$kind->value}:v{$appointment->schedule_version}");

            // One reminder ~24h before; none when confirmed inside that window.
            $reminderAt = $startUtc->subHours(config('brivia.appointments.reminder_hours_before'));
            if ($reminderAt->isFuture()) {
                $this->outbox->queue(NotificationKind::AppointmentReminder, $appointment->email, $payload, $appointment,
                    "appointment:{$appointment->id}:reminder:v{$appointment->schedule_version}", $reminderAt);
            }

            return $appointment;
        });
    }

    public function decline(User $actor, Appointment $appointment, int $expectedVersion, string $reason): Appointment
    {
        return $this->close($actor, $appointment, $expectedVersion, AppointmentStatus::Declined, [AppointmentStatus::Requested], $reason, NotificationKind::AppointmentDeclined);
    }

    public function cancel(User $actor, Appointment $appointment, int $expectedVersion, string $reason): Appointment
    {
        return $this->close($actor, $appointment, $expectedVersion, AppointmentStatus::Cancelled, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], $reason, NotificationKind::AppointmentCancelled);
    }

    public function complete(User $actor, Appointment $appointment, int $expectedVersion): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $expectedVersion) {
            $appointment = $this->lockFresh($appointment, $expectedVersion);

            if ($appointment->status !== AppointmentStatus::Confirmed || ! $appointment->confirmed_start_at_utc?->isPast()) {
                throw ValidationException::withMessages(['status' => 'Only confirmed appointments whose start time has passed can be marked completed.']);
            }

            $previous = $this->scheduleSnapshot($appointment);
            $appointment->forceFill(['status' => AppointmentStatus::Completed, 'lock_version' => $appointment->lock_version + 1])->save();
            $this->event($appointment, $actor, 'completed', $previous, null);
            $this->audit->record('appointment.completed', 'appointment', $appointment->id, ['status' => 'completed'], $actor->id);

            return $appointment;
        });
    }

    public function assign(User $actor, Appointment $appointment, int $expectedVersion, ?int $assigneeId): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $expectedVersion, $assigneeId) {
            $appointment = $this->lockFresh($appointment, $expectedVersion);

            if ($appointment->status !== AppointmentStatus::Requested) {
                throw ValidationException::withMessages(['assigned_to' => 'Confirmed appointments are reassigned through Reschedule, so overlaps are checked.']);
            }
            if ($assigneeId && ! User::operational()->whereKey($assigneeId)->exists()) {
                throw ValidationException::withMessages(['assigned_to' => 'Choose an active owner or operations manager.']);
            }

            $previous = $this->scheduleSnapshot($appointment);
            $appointment->forceFill(['assigned_to' => $assigneeId, 'lock_version' => $appointment->lock_version + 1])->save();
            $this->event($appointment, $actor, 'assigned', $previous, null);
            $this->audit->record('appointment.assigned', 'appointment', $appointment->id, ['assigned_to' => $assigneeId], $actor->id);

            return $appointment;
        });
    }

    private function close(User $actor, Appointment $appointment, int $expectedVersion, AppointmentStatus $to, array $from, string $reason, NotificationKind $kind): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $expectedVersion, $to, $from, $reason, $kind) {
            $appointment = $this->lockFresh($appointment, $expectedVersion);

            if (! in_array($appointment->status, $from, true)) {
                throw ValidationException::withMessages(['status' => "A {$appointment->status->value} appointment cannot be {$to->value}."]);
            }

            $previous = $this->scheduleSnapshot($appointment);
            $appointment->forceFill([
                'status' => $to,
                'cancellation_reason' => $reason,
                'schedule_version' => $appointment->schedule_version + 1,
                'lock_version' => $appointment->lock_version + 1,
            ])->save();

            $this->event($appointment, $actor, $to->value, $previous, $reason);
            $this->audit->record("appointment.{$to->value}", 'appointment', $appointment->id, ['status' => $to->value], $actor->id);
            $this->outbox->suppressObsolete($appointment, 'Appointment '.$to->value.'.');
            $this->outbox->queue($kind, $appointment->email, $this->payload($appointment) + ['reason' => $reason], $appointment,
                "appointment:{$appointment->id}:{$kind->value}:v{$appointment->schedule_version}");

            return $appointment;
        });
    }

    private function lockFresh(Appointment $appointment, int $expectedVersion): Appointment
    {
        $fresh = Appointment::lockForUpdate()->findOrFail($appointment->id);

        if ($fresh->lock_version !== $expectedVersion) {
            throw new StaleRecord;
        }

        return $fresh;
    }

    private function scheduleSnapshot(Appointment $a): array
    {
        return [
            'status' => $a->status->value,
            'assigned_to' => $a->assigned_to,
            'start_utc' => $a->confirmed_start_at_utc?->toIso8601String(),
            'end_utc' => $a->confirmed_end_at_utc?->toIso8601String(),
            'has_meeting_url' => filled($a->meeting_url),
        ];
    }

    private function event(Appointment $a, User $actor, string $type, array $previous, ?string $reason): void
    {
        $event = new AppointmentEvent;
        $event->forceFill([
            'appointment_id' => $a->id,
            'actor_id' => $actor->id,
            'event_type' => $type,
            'previous' => $previous,
            'new' => $this->scheduleSnapshot($a),
            'reason' => $reason ? mb_substr($reason, 0, 500) : null,
        ])->save();
    }

    /** Minimal email payload snapshot for this schedule version. */
    public function payload(Appointment $a): array
    {
        return [
            'reference' => $a->public_reference,
            'name' => $a->name,
            'type' => $a->type_snapshot,
            'duration' => $a->confirmed_start_at_utc && $a->confirmed_end_at_utc
                ? (int) $a->confirmed_start_at_utc->diffInMinutes($a->confirmed_end_at_utc) : $a->duration_minutes,
            'timezone' => $a->requested_timezone,
            'start_utc' => $a->confirmed_start_at_utc?->toIso8601String(),
            'meeting_url' => $a->meeting_url,
            'schedule_version' => $a->schedule_version,
        ];
    }

    /** Test-only hook to widen the race window in the real-MySQL concurrency test. */
    private function testingLockDelay(): void
    {
        if (app()->environment('testing') && ($ms = (int) config('brivia.testing.lock_delay_ms')) > 0) {
            usleep($ms * 1000);
        }
    }
}
