<?php

namespace App\Support;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Role and activation changes with final-owner protection and session revocation. */
class StaffAccessManager
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function changeRole(User $actor, User $user, Role $role): void
    {
        DB::transaction(function () use ($actor, $user, $role) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            if ($user->role === $role) {
                return;
            }

            if ($user->role === Role::Owner) {
                $this->ensureAnotherActiveOwner($user, 'role');
            }

            if ($role === Role::ContentEditor) {
                $this->ensureNoFutureAppointments($user, 'role');
            }

            $user->forceFill(['role' => $role])->save();
            $this->audit->record('staff.role_changed', 'user', $user->id, ['role' => $role], $actor->id);
        });
    }

    public function deactivate(User $actor, User $user): void
    {
        DB::transaction(function () use ($actor, $user) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            if (! $user->is_active) {
                return;
            }

            if ($user->role === Role::Owner) {
                $this->ensureAnotherActiveOwner($user, 'is_active');
            }

            $this->ensureNoFutureAppointments($user, 'is_active');

            $user->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            $this->revokeSessions($user);
            $this->audit->record('staff.deactivated', 'user', $user->id, ['is_active' => false], $actor->id);
        });
    }

    public function reactivate(User $actor, User $user): void
    {
        $user->forceFill(['is_active' => true, 'deactivated_at' => null])->save();
        $this->audit->record('staff.reactivated', 'user', $user->id, ['is_active' => true], $actor->id);
    }

    /** Deletes stored sessions for the user (database session driver). */
    public function revokeSessions(User $user, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    private function ensureAnotherActiveOwner(User $user, string $field): void
    {
        $others = User::query()->active()->where('role', Role::Owner->value)->whereKeyNot($user->id)->lockForUpdate()->count();

        if ($others === 0) {
            throw ValidationException::withMessages([
                $field => 'This is the last active owner. Promote or invite another owner first.',
            ]);
        }
    }

    private function ensureNoFutureAppointments(User $user, string $field): void
    {
        $upcoming = Appointment::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', [AppointmentStatus::Requested->value, AppointmentStatus::Confirmed->value])
            ->count();

        if ($upcoming > 0) {
            throw ValidationException::withMessages([
                $field => "This person is assigned to {$upcoming} open appointment(s). Reassign them first.",
            ]);
        }
    }
}
