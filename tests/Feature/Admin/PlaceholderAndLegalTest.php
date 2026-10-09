<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\LegalPage;
use App\Models\TeamMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceholderAndLegalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_placeholder_content_cannot_be_published_in_production(): void
    {
        $this->actingAsStaff(Role::Owner);
        $member = TeamMember::firstOrFail();
        $this->app->instance('env', 'production');
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->post("/admin/team/{$member->id}/publish")->assertSessionHas('publication_problems');
        $this->assertFalse($member->fresh()->isPublished());

        $privacy = LegalPage::where('key', 'privacy')->firstOrFail();
        $this->post("/admin/legal/{$privacy->id}/publish")->assertSessionHas('publication_problems');
        $this->assertFalse($privacy->fresh()->isPublished());
    }

    public function test_only_owner_can_approve_placeholders(): void
    {
        $member = TeamMember::firstOrFail();

        $this->actingAsStaff(Role::ContentEditor);
        $this->post("/admin/team/{$member->id}/approve")->assertForbidden();
        $this->assertTrue($member->fresh()->is_placeholder);

        $this->actingAsStaff(Role::Owner);
        $this->post("/admin/team/{$member->id}/approve")->assertSessionHas('status');
        $this->assertFalse($member->fresh()->is_placeholder);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.placeholder_approved', 'resource_id' => $member->id]);
    }

    public function test_changing_published_legal_text_requires_new_version(): void
    {
        $this->actingAsStaff(Role::Owner);
        $page = LegalPage::where('key', 'privacy')->firstOrFail();
        $page->forceFill(['is_placeholder' => false])->save();
        $page->markPublished();

        $this->put("/admin/legal/{$page->id}", ['title' => $page->title, 'body' => 'Changed text', 'version_label' => $page->version_label])->assertSessionHasErrors('version_label');
        $this->put("/admin/legal/{$page->id}", ['title' => $page->title, 'body' => 'Changed text', 'version_label' => '2026-10'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10', LegalPage::currentPrivacyVersion());
    }

    public function test_draft_privacy_version_is_labelled_honestly(): void
    {
        $this->assertSame('draft:draft-1', LegalPage::currentPrivacyVersion());
    }
}
