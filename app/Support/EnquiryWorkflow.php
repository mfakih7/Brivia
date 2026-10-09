<?php

namespace App\Support;

use App\Enums\EnquiryStatus;
use App\Exceptions\StaleRecord;
use App\Models\Enquiry;
use App\Models\EnquiryEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Enquiry triage: allowed status transitions, assignment and history, with stale-edit protection. */
class EnquiryWorkflow
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function transition(User $actor, Enquiry $enquiry, int $expectedVersion, EnquiryStatus $to): Enquiry
    {
        return DB::transaction(function () use ($actor, $enquiry, $expectedVersion, $to) {
            $enquiry = $this->lockFresh($enquiry, $expectedVersion);
            $from = $enquiry->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages(['status' => "An enquiry cannot move from {$from->label()} to {$to->label()}."]);
            }

            $enquiry->forceFill(['status' => $to, 'status_changed_at' => now(), 'lock_version' => $enquiry->lock_version + 1])->save();
            $this->event($enquiry, $actor, 'status', $from->value, $to->value);
            $this->audit->record('enquiry.status_changed', 'enquiry', $enquiry->id, ['status' => $to->value], $actor->id);

            return $enquiry;
        });
    }

    public function assign(User $actor, Enquiry $enquiry, int $expectedVersion, ?int $assigneeId): Enquiry
    {
        return DB::transaction(function () use ($actor, $enquiry, $expectedVersion, $assigneeId) {
            $enquiry = $this->lockFresh($enquiry, $expectedVersion);

            if ($assigneeId && ! User::operational()->whereKey($assigneeId)->exists()) {
                throw ValidationException::withMessages(['assigned_to' => 'Choose an active owner or operations manager.']);
            }

            $from = $enquiry->assigned_to;
            $enquiry->forceFill(['assigned_to' => $assigneeId, 'lock_version' => $enquiry->lock_version + 1])->save();
            $this->event($enquiry, $actor, 'assigned', $from ? (string) $from : null, $assigneeId ? (string) $assigneeId : null);
            $this->audit->record('enquiry.assigned', 'enquiry', $enquiry->id, ['assigned_to' => $assigneeId], $actor->id);

            return $enquiry;
        });
    }

    private function lockFresh(Enquiry $enquiry, int $expectedVersion): Enquiry
    {
        $fresh = Enquiry::lockForUpdate()->findOrFail($enquiry->id);

        if ($fresh->lock_version !== $expectedVersion) {
            throw new StaleRecord;
        }

        return $fresh;
    }

    private function event(Enquiry $enquiry, User $actor, string $type, ?string $from, ?string $to): void
    {
        $event = new EnquiryEvent;
        $event->forceFill(['enquiry_id' => $enquiry->id, 'actor_id' => $actor->id, 'event_type' => $type, 'from_value' => $from, 'to_value' => $to])->save();
    }
}
