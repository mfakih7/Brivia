<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\StaffAccessManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const CONTENT = ['/admin/services', '/admin/packages', '/admin/projects', '/admin/team', '/admin/about', '/admin/homepage', '/admin/consultation-types', '/admin/settings', '/admin/legal'];

    private const OPERATIONS = ['/admin/enquiries', '/admin/appointments'];

    private const OWNER_ONLY = ['/admin/staff', '/admin/audit-log'];

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (array_merge(['/admin'], self::CONTENT, self::OPERATIONS, self::OWNER_ONLY) as $uri) {
            $this->get($uri)->assertRedirect(route('admin.login'));
        }
    }

    public function test_owner_can_open_every_module(): void
    {
        $this->actingAsStaff(Role::Owner);

        foreach (array_merge(['/admin'], self::CONTENT, self::OPERATIONS, self::OWNER_ONLY) as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_content_editor_is_limited_to_content(): void
    {
        $this->actingAsStaff(Role::ContentEditor);

        foreach (self::CONTENT as $uri) {
            $this->get($uri)->assertOk();
        }
        foreach (array_merge(self::OPERATIONS, self::OWNER_ONLY) as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_operations_manager_is_limited_to_operations(): void
    {
        $this->actingAsStaff(Role::OperationsManager);

        foreach (self::OPERATIONS as $uri) {
            $this->get($uri)->assertOk();
        }
        foreach (array_merge(self::CONTENT, self::OWNER_ONLY) as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }

    public function test_content_editor_dashboard_shows_no_client_data(): void
    {
        Enquiry::factory()->create(['name' => 'Visible Prospect Name']);
        $this->actingAsStaff(Role::ContentEditor);

        $this->get('/admin')->assertOk()->assertDontSee('Visible Prospect Name')->assertDontSee('Recent enquiries')->assertSee('Content');
    }

    public function test_operations_dashboard_shows_real_counts(): void
    {
        Enquiry::factory()->count(2)->create(['name' => 'Visible Prospect Name']);
        $this->actingAsStaff(Role::OperationsManager);

        $this->get('/admin')->assertOk()->assertSee('Visible Prospect Name')->assertDontSee('Team members');
    }

    public function test_navigation_only_lists_allowed_modules(): void
    {
        $this->actingAsStaff(Role::OperationsManager);

        $this->get('/admin')->assertSee('Enquiries')->assertDontSee('href="'.route('admin.services.index').'"', false)->assertDontSee('Audit Log');
    }

    public function test_deactivated_user_is_blocked_on_next_request(): void
    {
        $owner = User::factory()->owner()->create();
        $editor = $this->actingAsStaff(Role::ContentEditor);
        $this->get('/admin')->assertOk();

        app(StaffAccessManager::class)->deactivate($owner, $editor);
        $editor->refresh(); // the test guard keeps the same model instance between requests

        $this->get('/admin/services')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
