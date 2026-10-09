<?php

namespace Tests\Feature\Admin;

use App\Enums\PriceMode;
use App\Enums\PublicationStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Models\HomepageContent;
use App\Models\Package;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\PublicContentCache;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAdminTest extends TestCase
{
    use RefreshDatabase;

    private function servicePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'New Products',
            'slug' => '',
            'summary' => 'From concept to launch.',
            'body' => 'Who it helps.',
            'deliverables' => ['Discovery', '', '  MVP  '],
            'engagement_steps' => ['Call'],
            'icon_key' => 'lightbulb',
            'sort_order' => 10,
        ], $overrides);
    }

    public function test_editor_creates_service_as_draft_with_normalized_lists(): void
    {
        $this->actingAsStaff(Role::ContentEditor);

        $this->post('/admin/services', $this->servicePayload())->assertRedirect();

        $service = Service::firstOrFail();
        $this->assertSame('new-products', $service->slug);
        $this->assertSame(['Discovery', 'MVP'], $service->deliverables);
        $this->assertSame(PublicationStatus::Draft, $service->status);
        $this->assertFalse($service->isPublished());
    }

    public function test_status_cannot_be_injected_through_the_form(): void
    {
        $this->actingAsStaff(Role::ContentEditor);

        $this->post('/admin/services', $this->servicePayload(['status' => 'published', 'published_at' => now()->toDateTimeString()]));

        $this->assertFalse(Service::firstOrFail()->isPublished());
    }

    public function test_publish_requires_complete_content_and_is_audited(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $service = Service::factory()->create(['body' => '', 'deliverables' => []]);

        $this->post("/admin/services/{$service->id}/publish")->assertSessionHas('publication_problems');
        $this->assertFalse($service->fresh()->isPublished());

        $service->forceFill(['body' => 'Body', 'deliverables' => ['One']])->save();
        $this->post("/admin/services/{$service->id}/publish")->assertSessionHas('status');
        $this->assertTrue($service->fresh()->isPublished());
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.published', 'resource_type' => 'service', 'resource_id' => $service->id]);

        $this->post("/admin/services/{$service->id}/unpublish");
        $this->assertFalse($service->fresh()->isPublished());
    }

    public function test_saving_fields_does_not_change_publication_state(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $service = Service::factory()->published()->create(['slug' => 'live']);

        $this->put("/admin/services/{$service->id}", $this->servicePayload(['slug' => 'live', 'title' => 'Renamed']))->assertSessionHasNoErrors();

        $this->assertTrue($service->fresh()->isPublished());
        $this->assertSame('Renamed', $service->fresh()->title);
    }

    public function test_published_slug_is_locked(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $service = Service::factory()->published()->create(['slug' => 'live']);

        $this->put("/admin/services/{$service->id}", $this->servicePayload(['slug' => 'changed']))->assertSessionHasErrors('slug');
        $this->assertSame('live', $service->fresh()->slug);
    }

    public function test_stale_edit_is_rejected(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $service = Service::factory()->create(['slug' => 'x']);
        $loaded = $service->updated_at->getTimestamp() - 60;

        $this->put("/admin/services/{$service->id}", $this->servicePayload(['slug' => 'x', 'loaded_version' => $loaded]))->assertSessionHasErrors('loaded_version');
    }

    public function test_referenced_service_cannot_be_deleted(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $service = Service::factory()->create();
        Package::factory()->create()->services()->attach($service);

        $this->delete("/admin/services/{$service->id}")->assertSessionHas('error');
        $this->assertModelExists($service);
        $this->assertStringContainsString('Unpublish', session('error'));
    }

    public function test_package_referenced_by_enquiry_cannot_be_deleted(): void
    {
        $this->actingAsStaff(Role::Owner);
        $package = Package::factory()->create();
        Enquiry::factory()->create(['package_id' => $package->id]);

        $this->delete("/admin/packages/{$package->id}")->assertSessionHas('error');
        $this->assertModelExists($package);
    }

    public function test_unreferenced_service_can_be_deleted(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $service = Service::factory()->create();

        $this->delete("/admin/services/{$service->id}")->assertRedirect(route('admin.services.index'));
        $this->assertModelMissing($service);
    }

    public function test_package_pricing_rules(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $base = ['name' => 'Website', 'slug' => '', 'summary' => 'S', 'features' => ['A'], 'exclusions' => [], 'currency' => 'USD', 'icon_key' => 'monitor', 'sort_order' => 1];

        $this->post('/admin/packages', $base + ['price_mode' => 'fixed', 'price_amount' => ''])->assertSessionHasErrors('price_amount');
        $this->post('/admin/packages', $base + ['price_mode' => 'fixed', 'price_amount' => '-5'])->assertSessionHasErrors('price_amount');
        $this->post('/admin/packages', $base + ['price_mode' => 'bogus'])->assertSessionHasErrors('price_mode');
        $this->post('/admin/packages', ['price_mode' => 'quote', 'price_amount' => '999', 'currency' => 'XXX'] + $base)->assertSessionHasErrors('currency');

        $this->post('/admin/packages', $base + ['price_mode' => 'quote', 'price_amount' => '999'])->assertSessionHasNoErrors();
        $package = Package::firstOrFail();
        $this->assertNull($package->price_amount, 'Quote mode must not keep an amount.');

        $this->put("/admin/packages/{$package->id}", $base + ['slug' => $package->slug, 'price_mode' => 'starting_from', 'price_amount' => '1500'])->assertSessionHasNoErrors();
        $package->refresh();
        $this->assertSame(PriceMode::StartingFrom, $package->price_mode);
        $this->assertSame('From $1,500', $package->priceLabel());
        $this->assertTrue(AuditLog::where('resource_type', 'package')->get()->contains(fn ($log) => (float) ($log->changed_fields['price_amount'] ?? 0) === 1500.0));
    }

    public function test_content_writes_invalidate_public_cache(): void
    {
        $before = PublicContentCache::version();
        Service::factory()->create();

        $this->assertGreaterThan($before, PublicContentCache::version());
    }

    public function test_operations_manager_cannot_write_content(): void
    {
        $this->actingAsStaff(Role::OperationsManager);
        $service = Service::factory()->create();

        $this->post('/admin/services', $this->servicePayload())->assertForbidden();
        $this->put("/admin/services/{$service->id}", $this->servicePayload())->assertForbidden();
        $this->post("/admin/services/{$service->id}/publish")->assertForbidden();
        $this->delete("/admin/services/{$service->id}")->assertForbidden();
        $this->get("/admin/services/{$service->id}/preview")->assertForbidden();
        $this->put('/admin/settings', ['business_name' => 'x'])->assertForbidden();
    }

    public function test_draft_preview_is_authorised_private_and_escaped(): void
    {
        $service = Service::factory()->create(['title' => 'Secret draft', 'body' => '<script>alert(1)</script>']);

        $this->get("/admin/services/{$service->id}/preview")->assertRedirect(route('admin.login'));

        $this->actingAsStaff(Role::ContentEditor);
        $response = $this->get("/admin/services/{$service->id}/preview")->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Secret draft')
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_reorder_validates_ids_and_updates_in_transaction(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        [$a, $b] = Service::factory()->count(2)->create();

        $this->post('/admin/services/reorder', ['order' => [$a->id => 20, 99999 => 10]])->assertSessionHasErrors('order');
        $this->post('/admin/services/reorder', ['order' => [$a->id => 20, $b->id => 10]])->assertSessionHasNoErrors();

        $this->assertSame(20, $a->fresh()->sort_order);
        $this->assertSame(10, $b->fresh()->sort_order);
    }

    public function test_list_search_escapes_wildcards(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        Service::factory()->create(['title' => 'Alpha']);
        Service::factory()->create(['title' => '100% Beta']);

        $this->get('/admin/services?q=%25')->assertOk()->assertSee('100% Beta')->assertDontSee('Alpha');
        $this->get('/admin/services?q=_')->assertOk()->assertDontSee('Alpha');
        $this->get('/admin/services?status=evil')->assertOk();
    }

    public function test_settings_reject_unsafe_urls(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $base = ['business_name' => 'BRIVIA', 'default_timezone' => 'Asia/Beirut'];

        $this->put('/admin/settings', $base + ['whatsapp_url' => 'http://wa.me/123'])->assertSessionHasErrors('whatsapp_url');
        $this->put('/admin/settings', $base + ['whatsapp_url' => 'https://evil.example/wa'])->assertSessionHasErrors('whatsapp_url');
        $this->put('/admin/settings', $base + ['social_links' => ['linkedin' => 'javascript:alert(1)']])->assertSessionHasErrors('social_links.linkedin');
        $this->put('/admin/settings', $base + ['social_links' => ['evil' => 'https://x.test']])->assertSessionHasErrors('social_links');
        $this->put('/admin/settings', ['default_timezone' => 'Mars/Base'] + $base)->assertSessionHasErrors('default_timezone');

        $this->put('/admin/settings', $base + ['whatsapp_url' => 'https://wa.me/9611234567', 'social_links' => ['linkedin' => 'https://www.linkedin.com/company/x', 'github' => '']])->assertSessionHasNoErrors();
        $this->assertSame(['linkedin' => 'https://www.linkedin.com/company/x'], SiteSetting::current()->social_links);
    }

    public function test_homepage_cta_must_use_allowlisted_route(): void
    {
        $this->actingAsStaff(Role::ContentEditor);
        $base = ['hero_headline' => 'H', 'primary_cta_label' => 'Go'];

        $this->put('/admin/homepage', $base + ['primary_cta_route' => 'admin.dashboard'])->assertSessionHasErrors('primary_cta_route');
        $this->put('/admin/homepage', $base + ['primary_cta_route' => 'contact', 'process_steps' => [['title' => 'Discover', 'body' => 'x', 'evil' => 'y'], ['title' => '', 'body' => '']], 'section_visibility' => ['process' => '0', 'services' => '1']])->assertSessionHasNoErrors();

        $home = HomepageContent::current();
        $this->assertSame([['title' => 'Discover', 'body' => 'x']], $home->process_steps);
        $this->assertFalse($home->showsSection('process'));
        $this->assertFalse($home->showsSection('projects'));
        $this->assertTrue($home->showsSection('services'));
    }

    public function test_admin_screens_render_for_editor(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsStaff(Role::ContentEditor);
        $service = Service::first();
        $package = Package::first();

        foreach (['/admin/services', '/admin/services/create', "/admin/services/{$service->id}/edit", '/admin/packages', "/admin/packages/{$package->id}/edit",
            '/admin/projects', '/admin/projects/create', '/admin/project-categories', '/admin/team', '/admin/team/1/edit',
            '/admin/consultation-types', '/admin/consultation-types/1/edit', '/admin/about', '/admin/homepage', '/admin/settings', '/admin/legal', '/admin/legal/1/edit'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}
