<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Package;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Integrated checks for spec 04 "Acceptance scenarios" not already covered end to end elsewhere. */
class AcceptanceScenariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_1_editor_publishes_package_and_new_pricing_appears_publicly(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsStaff(Role::ContentEditor);
        $package = Package::where('slug', 'business-website')->firstOrFail();

        $this->get('/packages')->assertDontSee('Business Website');
        $this->get('/sitemap.xml')->assertOk(); // warm the cached sitemap/legal fragments

        $this->post("/admin/packages/{$package->id}/publish")->assertSessionHas('status');
        $this->get('/packages')->assertSee('Business Website')->assertSee('Request a quote');

        $this->put("/admin/packages/{$package->id}", [
            'name' => 'Business Website', 'slug' => 'business-website', 'summary' => 'Updated summary for launch.',
            'features' => ['Strategy'], 'exclusions' => [], 'price_mode' => 'starting_from', 'price_amount' => '3200',
            'currency' => 'USD', 'billing_label' => 'per project', 'icon_key' => 'monitor', 'sort_order' => 10,
            'loaded_version' => $package->fresh()->updated_at->getTimestamp(),
        ])->assertSessionHasNoErrors();

        $this->get('/packages')->assertSee('From $3,200 per project')->assertSee('Updated summary for launch.');
        $this->get('/')->assertSee('From $3,200 per project');
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.updated', 'resource_type' => 'package', 'resource_id' => $package->id]);

        $this->post("/admin/packages/{$package->id}/unpublish");
        $this->get('/packages')->assertDontSee('Business Website');
    }
}
