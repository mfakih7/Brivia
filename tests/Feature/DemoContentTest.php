<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\DemoRecord;
use App\Models\Media;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DemoContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_demo_seeder_populates_published_labelled_content_idempotently(): void
    {
        Mail::fake();
        Queue::fake();
        $owner = User::factory()->owner()->create();
        $passwordHash = $owner->password;

        $this->seed(DemoContentSeeder::class);
        $counts = [Service::count(), Package::count(), Project::count(), TeamMember::count(), Media::count(), DemoRecord::count()];
        $this->seed(DemoContentSeeder::class);

        $this->assertSame($counts, [Service::count(), Package::count(), Project::count(), TeamMember::count(), Media::count(), DemoRecord::count()], 'Re-running must not duplicate anything.');
        $this->assertSame(4, Service::published()->count());
        $this->assertSame(6, Project::published()->count());
        $this->assertSame(18, Media::count(), 'Cover + 2 gallery images per project, generated locally.');
        foreach (Media::all() as $media) {
            Storage::disk('public')->assertExists($media->storage_path);
        }

        // Starter drafts and accounts are untouched; nothing is mailed or queued.
        $this->assertFalse(Service::where('slug', 'new-products')->first()->isPublished());
        $this->assertSame($passwordHash, $owner->fresh()->password);
        $this->assertSame(1, User::count());
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();

        // Honest labelling.
        $this->assertTrue(TeamMember::published()->get()->every(fn ($m) => $m->is_placeholder && str_contains($m->name, 'placeholder')));
        $this->assertSame(5, Project::where('work_origin', 'concept')->count());
        $this->assertStringContainsString('placeholder', Project::where('work_origin', 'founder_experience')->first()->title);
        $this->assertTrue(Project::all()->every(fn ($p) => $p->outcomes === null && $p->client_display_name === null));

        foreach (['/', '/services', '/services/demo-new-products', '/packages', '/projects', '/projects/demo-pulse-operations-dashboard', '/about', '/contact', '/consultation'] as $uri) {
            $this->get($uri)->assertOk();
        }
        $this->get('/')->assertSee('Pulse — operations dashboard')->assertSee('Sample concept')->assertSee('A clear path to your next product');
        $this->get('/packages')->assertSee('From $2,500 (illustrative demo price)')->assertSee('Request a quote');
        $this->get('/about')->assertSee('Founder A (placeholder name)')->assertSee('Clarity first');
        $this->get('/projects/demo-earlier-experience-placeholder')->assertSee('Earlier founder experience')->assertSee('PLACEHOLDER');
        $this->get('/contact')->assertSee('hello@brivia.example');
    }

    public function test_real_content_is_never_overwritten(): void
    {
        $real = Service::factory()->create(['slug' => 'demo-new-products', 'title' => 'Real service']);
        SiteSetting::current()->fill(['email' => 'real@company.test'])->save();

        $this->seed(DemoContentSeeder::class);

        $this->assertSame('Real service', $real->fresh()->title);
        $this->assertNull(DemoRecord::where('demo_key', 'service:new-products')->first());
        $this->assertSame('real@company.test', SiteSetting::current()->email);
        $this->assertSame('+1 555 0100', SiteSetting::current()->phone, 'Blank fields are filled.');
    }

    public function test_cleanup_removes_only_demo_content_and_restores_blank_fields(): void
    {
        $realProject = Project::factory()->published()->create(['slug' => 'real-project']);
        $this->seed(DemoContentSeeder::class);
        $paths = Media::pluck('storage_path');
        SiteSetting::current()->fill(['phone' => '+961 1 234 567'])->save(); // edited after seeding

        $this->artisan('brivia:demo-content:remove', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, DemoRecord::count());
        $this->assertSame(0, Media::count());
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertModelExists($realProject);
        $this->assertSame(4, Service::count(), 'Only the 4 starter drafts remain.');
        $this->assertNull(SiteSetting::current()->email, 'Demo value restored to the original blank.');
        $this->assertSame('+961 1 234 567', SiteSetting::current()->phone, 'Edited values are kept.');
        $this->assertSame([], CompanyProfile::current()->values);
    }

    public function test_demo_seeder_and_cleanup_refuse_outside_local_and_testing(): void
    {
        $this->app->instance('env', 'production');

        try {
            $this->app->make(DemoContentSeeder::class)->run();
            $this->fail('Seeder must abort in production.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Aborted', $e->getMessage());
        }
        $this->assertSame(0, DemoRecord::count());

        $this->artisan('brivia:demo-content:remove', ['--force' => true])->assertFailed();
    }

    public function test_database_seeder_does_not_include_demo_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, DemoRecord::count());
        $this->assertSame(0, Project::count());
    }
}
