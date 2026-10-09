<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Mail\StaffInvitationMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StaffAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_invites_changes_roles_and_deactivates(): void
    {
        Mail::fake();
        $owner = $this->actingAsStaff(Role::Owner);
        $editor = User::factory()->create();

        $this->get('/admin/staff')->assertOk()->assertSee($editor->email);
        $this->post('/admin/staff/invitations', ['email' => 'new@example.test', 'role' => 'operations_manager'])->assertSessionHas('status');
        Mail::assertQueued(StaffInvitationMail::class);

        $this->put("/admin/staff/{$editor->id}/role", ['role' => 'operations_manager'])->assertSessionHas('status');
        $this->assertSame(Role::OperationsManager, $editor->fresh()->role);

        $this->post("/admin/staff/{$editor->id}/deactivate")->assertSessionHas('status');
        $this->assertFalse($editor->fresh()->is_active);

        $this->post("/admin/staff/{$owner->id}/deactivate")->assertSessionHas('error');
        $this->put("/admin/staff/{$owner->id}/role", ['role' => 'content_editor'])->assertSessionHasErrors('role');
        $this->assertSame(Role::Owner, $owner->fresh()->role);
    }

    public function test_invitations_are_rate_limited(): void
    {
        Mail::fake();
        $this->actingAsStaff(Role::Owner);

        foreach (range(1, 3) as $i) {
            $this->post('/admin/staff/invitations', ['email' => "p{$i}@example.test", 'role' => 'content_editor']);
        }
        $this->post('/admin/staff/invitations', ['email' => 'p4@example.test', 'role' => 'content_editor'])->assertStatus(429);
    }

    public function test_non_owners_cannot_manage_staff_or_view_audit(): void
    {
        $target = User::factory()->create();

        foreach ([Role::ContentEditor, Role::OperationsManager] as $role) {
            $this->actingAsStaff($role);
            $this->post('/admin/staff/invitations', ['email' => 'x@example.test', 'role' => 'owner'])->assertForbidden();
            $this->put("/admin/staff/{$target->id}/role", ['role' => 'owner'])->assertForbidden();
            $this->post("/admin/staff/{$target->id}/deactivate")->assertForbidden();
            $this->get('/admin/audit-log')->assertForbidden();
        }
    }

    public function test_owner_can_filter_audit_log(): void
    {
        $this->actingAsStaff(Role::Owner);
        app(AuditLogger::class)->record('content.published', 'service', 5, ['status' => 'published']);

        $this->get('/admin/audit-log?type=service&action=content.')->assertOk()->assertSee('content.published');
        $this->get('/admin/audit-log?type=evil&action=%27%20OR%201=1')->assertOk();
        $this->assertSame(1, AuditLog::count());
    }
}
