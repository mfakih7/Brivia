<?php

namespace Tests\Feature\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Mail\StaffInvitationMail;
use App\Models\AdminInvitation;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\InvitationManager;
use App\Support\StaffAccessManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_active_owner_cannot_be_deactivated_or_demoted(): void
    {
        $owner = User::factory()->owner()->create();
        $manager = app(StaffAccessManager::class);

        foreach ([fn () => $manager->deactivate($owner, $owner), fn () => $manager->changeRole($owner, $owner, Role::ContentEditor)] as $action) {
            try {
                $action();
                $this->fail('Expected the final owner to be protected.');
            } catch (ValidationException) {
                $this->assertTrue($owner->fresh()->is_active);
                $this->assertSame(Role::Owner, $owner->fresh()->role);
            }
        }
    }

    public function test_owner_can_be_deactivated_when_another_active_owner_exists(): void
    {
        config(['session.driver' => 'database']);
        $owner = User::factory()->owner()->create();
        $other = User::factory()->owner()->create();
        DB::table('sessions')->insert(['id' => 's1', 'user_id' => $other->id, 'payload' => '', 'last_activity' => time()]);

        app(StaffAccessManager::class)->deactivate($owner, $other);

        $this->assertFalse($other->fresh()->is_active);
        $this->assertDatabaseMissing('sessions', ['id' => 's1']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.deactivated', 'resource_id' => $other->id]);
    }

    public function test_staff_with_open_appointments_must_be_reassigned_first(): void
    {
        $owner = User::factory()->owner()->create();
        $ops = User::factory()->operationsManager()->create();
        Appointment::factory()->create(['assigned_to' => $ops->id, 'status' => AppointmentStatus::Confirmed]);

        $this->expectException(ValidationException::class);
        app(StaffAccessManager::class)->deactivate($owner, $ops);
    }

    public function test_role_is_not_mass_assignable(): void
    {
        $user = new User(['name' => 'X', 'email' => 'x@example.test', 'password' => 'x', 'role' => 'owner', 'is_active' => false]);

        $this->assertNull($user->role);
        $this->assertNull($user->is_active);
    }

    public function test_invitation_stores_only_a_hash_and_is_single_use(): void
    {
        Mail::fake();
        $owner = User::factory()->owner()->create();

        $invitation = app(InvitationManager::class)->invite($owner, 'New.Person@Example.test', Role::OperationsManager);

        $url = null;
        Mail::assertQueued(StaffInvitationMail::class, function ($mail) use (&$url) {
            $url = $mail->acceptUrl;

            return $mail->hasTo('new.person@example.test');
        });
        $token = basename($url);
        $this->assertSame(64, strlen($token));
        $this->assertSame(hash('sha256', $token), $invitation->token_hash);
        $this->assertDatabaseMissing('admin_invitations', ['token_hash' => $token]);

        $this->get("/admin/invitations/{$token}")->assertOk()->assertSee('Operations manager');
        $this->post("/admin/invitations/{$token}", [
            'name' => 'New Person', 'password' => 'Strong-Password-1!', 'password_confirmation' => 'Strong-Password-1!',
        ])->assertRedirect(route('admin.dashboard'));

        $created = User::where('email', 'new.person@example.test')->first();
        $this->assertSame(Role::OperationsManager, $created->role);
        $this->assertAuthenticatedAs($created);

        auth()->logout();
        $this->post("/admin/invitations/{$token}", [
            'name' => 'Again', 'password' => 'Strong-Password-1!', 'password_confirmation' => 'Strong-Password-1!',
        ])->assertSessionHasErrors('token');
        $this->assertSame(1, User::where('email', 'new.person@example.test')->count());
    }

    public function test_expired_invitation_is_rejected(): void
    {
        Mail::fake();
        $owner = User::factory()->owner()->create();
        app(InvitationManager::class)->invite($owner, 'late@example.test', Role::ContentEditor);
        $url = null;
        Mail::assertQueued(StaffInvitationMail::class, function ($mail) use (&$url) {
            $url = $mail->acceptUrl;

            return true;
        });

        $this->travel(49)->hours();

        $this->get($url)->assertOk()->assertSee('invalid, expired');
        $this->post($url, ['name' => 'Late', 'password' => 'Strong-Password-1!', 'password_confirmation' => 'Strong-Password-1!'])->assertSessionHasErrors('token');
        $this->assertDatabaseMissing('users', ['email' => 'late@example.test']);
    }

    public function test_reinviting_revokes_the_previous_invitation(): void
    {
        Mail::fake();
        $owner = User::factory()->owner()->create();
        $first = app(InvitationManager::class)->invite($owner, 'a@example.test', Role::ContentEditor);
        app(InvitationManager::class)->invite($owner, 'a@example.test', Role::ContentEditor);

        $this->assertNotNull($first->fresh()->revoked_at);
        $this->assertSame(1, AdminInvitation::pending()->count());
    }

    public function test_create_owner_command_bootstraps_once(): void
    {
        $this->artisan('brivia:create-owner')
            ->expectsQuestion('Full name', 'First Owner')
            ->expectsQuestion('Email address', 'owner@example.test')
            ->expectsQuestion('Password (min. 12 characters, mixed case, number, symbol)', 'Very-Strong-Pass-1!')
            ->expectsQuestion('Confirm password', 'Very-Strong-Pass-1!')
            ->assertSuccessful();

        $owner = User::where('email', 'owner@example.test')->first();
        $this->assertSame(Role::Owner, $owner->role);
        $this->assertSame(1, AuditLog::where('action', 'staff.owner_bootstrapped')->count());

        $this->artisan('brivia:create-owner')->assertFailed();
    }
}
