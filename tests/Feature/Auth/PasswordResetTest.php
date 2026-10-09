<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_request_response_is_generic(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'staff@example.test']);

        $this->post('/admin/forgot-password', ['email' => 'nobody@example.test'])->assertSessionHas('status');
        $generic = session('status');
        $this->post('/admin/forgot-password', ['email' => 'staff@example.test'])->assertSessionHas('status', $generic);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            return str_contains($notification->toMail($user)->actionUrl, '/admin/reset-password/');
        });
    }

    public function test_inactive_user_receives_no_reset_link(): void
    {
        Notification::fake();
        User::factory()->inactive()->create(['email' => 'old@example.test']);

        $this->post('/admin/forgot-password', ['email' => 'old@example.test'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_reset_changes_password_and_revokes_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['email' => 'staff@example.test']);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $token = Password::createToken($user);

        $this->post('/admin/reset-password', [
            'token' => $token, 'email' => 'staff@example.test',
            'password' => 'New-Password-123!', 'password_confirmation' => 'New-Password-123!',
        ])->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('New-Password-123!', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.test']);
        $token = Password::createToken($user);

        $this->post('/admin/reset-password', [
            'token' => $token, 'email' => 'staff@example.test', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
