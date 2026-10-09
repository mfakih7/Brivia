<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_private_and_not_indexed(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Sign in');
    }

    public function test_valid_credentials_go_directly_to_the_dashboard(): void
    {
        $user = User::factory()->owner()->create(['email' => 'staff@example.test']);
        $this->get('/admin/login');
        $sessionBefore = session()->getId();

        $this->post('/admin/login', ['email' => 'STAFF@example.test', 'password' => 'Correct-Horse-9!'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionBefore, session()->getId(), 'Session ID must be regenerated on sign-in.');
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->get('/admin')->assertOk()->assertSee('Dashboard');
        $this->get('/admin/services')->assertOk();
    }

    public function test_intended_admin_page_is_restored_after_sign_in(): void
    {
        User::factory()->owner()->create(['email' => 'staff@example.test']);

        $this->get('/admin/enquiries')->assertRedirect(route('admin.login'));
        $this->post('/admin/login', ['email' => 'staff@example.test', 'password' => 'Correct-Horse-9!'])
            ->assertRedirect(route('admin.enquiries.index'));
    }

    public function test_role_restrictions_apply_after_password_sign_in(): void
    {
        User::factory()->contentEditor()->create(['email' => 'editor@example.test']);

        $this->post('/admin/login', ['email' => 'editor@example.test', 'password' => 'Correct-Horse-9!'])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/services')->assertOk();
        $this->get('/admin/enquiries')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
        $this->get('/admin/audit-log')->assertForbidden();
    }

    public function test_two_factor_routes_no_longer_exist(): void
    {
        $this->actingAsStaff();

        foreach (['/admin/two-factor/setup', '/admin/two-factor/challenge', '/admin/account/recovery-codes', '/admin/confirm-password'] as $uri) {
            $this->get($uri)->assertNotFound();
        }
        $this->post('/admin/two-factor/challenge', ['code' => '123456'])->assertNotFound();
    }

    public function test_previously_enrolled_account_signs_in_with_password_after_migration(): void
    {
        $migration = require database_path('migrations/2026_10_09_000100_remove_two_factor_columns_from_users_table.php');
        $migration->down(); // restore the legacy columns as they existed before this change

        $user = User::factory()->owner()->create(['email' => 'enrolled@example.test']);
        DB::table('users')->where('id', $user->id)->update([
            'two_factor_secret' => 'legacy-encrypted-secret',
            'two_factor_recovery_codes' => 'legacy-encrypted-codes',
            'two_factor_confirmed_at' => now(),
        ]);
        $before = DB::table('users')->where('id', $user->id)->first(['email', 'password', 'role', 'is_active']);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('users', 'two_factor_secret'));
        $this->assertEquals($before, DB::table('users')->where('id', $user->id)->first(['email', 'password', 'role', 'is_active']));

        $this->post('/admin/login', ['email' => 'enrolled@example.test', 'password' => 'Correct-Horse-9!'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk();
    }

    public function test_failures_are_generic(): void
    {
        User::factory()->create(['email' => 'staff@example.test']);

        $wrongPassword = $this->post('/admin/login', ['email' => 'staff@example.test', 'password' => 'nope'])->assertSessionHasErrors('email');
        $wrongMessage = session('errors')->first('email');

        $this->post('/admin/login', ['email' => 'nobody@example.test', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertSame($wrongMessage, session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        User::factory()->inactive()->create(['email' => 'old@example.test']);

        $this->post('/admin/login', ['email' => 'old@example.test', 'password' => 'Correct-Horse-9!'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'staff@example.test']);

        foreach (range(1, 5) as $i) {
            $this->post('/admin/login', ['email' => 'staff@example.test', 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => 'staff@example.test', 'password' => 'Correct-Horse-9!'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many sign-in attempts', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_logout_invalidates_session(): void
    {
        $this->actingAsStaff();

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/services')->assertRedirect(route('admin.login'));
    }
}
