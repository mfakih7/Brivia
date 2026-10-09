<?php

namespace App\Support;

use App\Enums\Role;
use App\Mail\StaffInvitationMail;
use App\Models\AdminInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationManager
{
    public const LIFETIME_HOURS = 48;

    public function __construct(private readonly AuditLogger $audit) {}

    /** Creates a single-use invitation and queues the setup email. Only the hash is stored. */
    public function invite(User $actor, string $email, Role $role): AdminInvitation
    {
        $email = Str::lower(trim($email));

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'A staff account already exists for this email address.']);
        }

        $plainToken = Str::random(64);

        $invitation = DB::transaction(function () use ($actor, $email, $role, $plainToken) {
            AdminInvitation::query()->pending()->where('email', $email)->update(['revoked_at' => now()]);

            $invitation = new AdminInvitation;
            $invitation->forceFill([
                'email' => $email,
                'role' => $role,
                'token_hash' => AdminInvitation::hashToken($plainToken),
                'invited_by' => $actor->id,
                'expires_at' => now()->addHours(self::LIFETIME_HOURS),
            ])->save();

            $this->audit->record('staff.invited', 'invitation', $invitation->id, ['role' => $role], $actor->id);

            return $invitation;
        });

        Mail::to($email)->queue(new StaffInvitationMail($invitation, route('admin.invitations.show', $plainToken)));

        return $invitation;
    }

    public function revoke(User $actor, AdminInvitation $invitation): void
    {
        if ($invitation->isPending()) {
            $invitation->forceFill(['revoked_at' => now()])->save();
            $this->audit->record('staff.invitation_revoked', 'invitation', $invitation->id, [], $actor->id);
        }
    }

    public function findPending(string $plainToken): ?AdminInvitation
    {
        return AdminInvitation::query()->pending()->where('token_hash', AdminInvitation::hashToken($plainToken))->first();
    }

    /** Atomically consumes the invitation and creates the staff account. */
    public function accept(string $plainToken, string $name, string $password): User
    {
        return DB::transaction(function () use ($plainToken, $name, $password) {
            $invitation = AdminInvitation::query()
                ->where('token_hash', AdminInvitation::hashToken($plainToken))
                ->lockForUpdate()
                ->first();

            if (! $invitation || ! $invitation->isPending() || User::where('email', $invitation->email)->exists()) {
                throw ValidationException::withMessages(['token' => 'This invitation is invalid, expired, or already used.']);
            }

            $user = new User(['name' => $name, 'email' => $invitation->email, 'password' => $password]);
            $user->forceFill(['role' => $invitation->role, 'is_active' => true, 'email_verified_at' => now()])->save();

            $invitation->forceFill(['accepted_at' => now()])->save();
            $this->audit->record('staff.invitation_accepted', 'user', $user->id, ['role' => $invitation->role], $user->id);

            return $user;
        });
    }
}
