<?php

namespace Tests\Feature\Public;

use App\Enums\LegalPageKey;
use App\Enums\MediaRole;
use App\Enums\Role;
use App\Enums\WorkOrigin;
use App\Models\ConsultationType;
use App\Models\HomepageContent;
use App\Models\LegalPage;
use App\Models\Media;
use App\Models\Package;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectMedia;
use App\Models\Service;
use App\Models\SiteSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_public_pages_render_with_seeded_drafts_only(): void
    {
        foreach (['/', '/services', '/packages', '/projects', '/about', '/contact', '/consultation'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('BRIVIA');
        }

        // Drafts are invisible: seeded services/packages are not shown anywhere publicly.
        $this->get('/')->assertDontSee('Redesign &amp; Modernization', false)->assertDontSee('A clear path to your next product');
        $this->get('/services/new-products')->assertNotFound();
        $this->get('/privacy')->assertNotFound();
        $this->get('/terms')->assertNotFound();
    }

    public function test_published_content_appears_and_empty_sections_are_omitted(): void
    {
        Service::where('slug', 'new-products')->first()->markPublished();

        $this->get('/')->assertOk()
            ->assertSee('A clear path to your next product')
            ->assertSee('New Products')
            ->assertDontSee('Choose your starting point')
            ->assertDontSee('Ideas brought to life');

        $this->get('/services/new-products')->assertOk()->assertSee('Typical deliverables')->assertSee('confirmed in a written proposal');
    }

    public function test_hidden_homepage_sections_are_not_rendered(): void
    {
        Service::where('slug', 'new-products')->first()->markPublished();
        $home = HomepageContent::current();
        $home->section_visibility = ['services' => false] + $home->section_visibility;
        $home->save();

        $this->get('/')->assertOk()->assertDontSee('A clear path to your next product');
    }

    public function test_unknown_and_invalid_slugs_return_404(): void
    {
        $this->get('/services/does-not-exist')->assertNotFound();
        $this->get('/services/New-Products')->assertNotFound();
        $this->get('/projects/nope')->assertNotFound();
        $this->get('/this-route-does-not-exist')->assertNotFound()->assertSee('We couldn’t find that page');
    }

    public function test_draft_project_absent_from_list_detail_and_sitemap_but_previewable(): void
    {
        $draft = Project::factory()->create(['title' => 'Secret Draft Project', 'slug' => 'secret-draft']);

        $this->get('/projects')->assertDontSee('Secret Draft Project');
        $this->get('/projects/secret-draft')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('secret-draft');
        $this->get("/admin/projects/{$draft->id}/preview")->assertRedirect(route('admin.login'));

        $this->actingAsStaff(Role::ContentEditor);
        $this->get("/admin/projects/{$draft->id}/preview")->assertOk()
            ->assertSee('Secret Draft Project')->assertSee('Private preview')
            ->assertSee('noindex, nofollow', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_project_listing_filters_paginates_and_labels_origin(): void
    {
        $web = ProjectCategory::where('slug', 'web-application')->first();
        $site = ProjectCategory::where('slug', 'business-website')->first();
        foreach (range(1, 11) as $i) {
            Project::factory()->published()->create([
                'title' => "Web project {$i}", 'slug' => "web-project-{$i}", 'category_id' => $web->id,
                'published_at' => now()->subDays($i), 'work_origin' => WorkOrigin::Concept,
            ]);
        }
        Project::factory()->published()->create(['title' => 'Founder site', 'slug' => 'founder-site', 'category_id' => $site->id, 'work_origin' => WorkOrigin::FounderExperience, 'published_at' => now()->subHour()]);

        $page1 = $this->get('/projects')->assertOk();
        $page1->assertSeeInOrder(['Founder site', 'Web project 1', 'Web project 2']);
        $page1->assertSee('Earlier founder experience')->assertSee('Sample concept')->assertDontSee('Web project 9');

        $this->get('/projects?category=web-application')->assertOk()->assertDontSee('Founder site')->assertSee('Web project 9')->assertSee('category=web-application&amp;page=2', false);
        $this->get('/projects?category=web-application&page=2')->assertOk()->assertSee('Web project 10')->assertSee('Web project 11')->assertDontSee('Web project 1<', false);
        $this->get('/projects?category=dashboard')->assertNotFound(); // category without published projects
        $this->get('/projects?category=evil%27--')->assertNotFound();
        $this->get('/projects?page=99')->assertNotFound();

        $this->get('/projects/founder-site')->assertOk()->assertSee('earlier experience, before BRIVIA was founded');
        $this->get('/projects/web-project-1')->assertOk()->assertSee('It is not client work');
    }

    public function test_case_study_shows_cover_gallery_and_escapes_content(): void
    {
        $project = Project::factory()->published()->create(['title' => '<script>alert(1)</script> App', 'slug' => 'xss-app', 'solution' => '<img src=x onerror=alert(1)>']);
        $media = Media::factory()->create(['alt_text' => 'Dashboard overview']);
        $link = new ProjectMedia(['caption' => 'Main screen']);
        $link->forceFill(['project_id' => $project->id, 'media_id' => $media->id, 'role' => MediaRole::Gallery])->save();

        $response = $this->get('/projects/xss-app')->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x', false);
        $response->assertSee('&lt;script&gt;', false)->assertSee('Dashboard overview')->assertSee('Main screen')->assertSee('loading="lazy"', false);
        $response->assertSee(route('contact', ['project' => 'xss-app']), false);
    }

    public function test_packages_page_never_invents_prices(): void
    {
        Package::where('slug', 'business-website')->first()->markPublished();

        $this->get('/packages')->assertOk()
            ->assertSee('Business Website')
            ->assertSee('Request a quote')
            ->assertSee('Selecting a package is never a purchase')
            ->assertSee('Need something different?')
            ->assertDontSee('$');
    }

    public function test_contact_preselects_only_published_context(): void
    {
        $package = Package::where('slug', 'business-website')->first();
        $draftService = Service::where('slug', 'new-products')->first();

        $this->get('/contact?package=business-website&service=new-products')->assertOk()
            ->assertDontSee('value="business-website"', false)
            ->assertDontSee('value="new-products"', false);

        $package->markPublished();
        $project = Project::factory()->published()->create(['title' => 'Context Project', 'slug' => 'context-project']);
        $this->get('/contact?package=business-website&project=context-project')->assertOk()
            ->assertSee('<option value="business-website" selected>', false)
            ->assertSee('This enquiry relates to: Context Project');
        $this->get('/contact?project=does-not-exist')->assertOk()->assertDontSee('This enquiry relates to');
        $this->assertFalse($draftService->fresh()->isPublished());
    }

    public function test_forms_are_live_with_session_bound_submission_keys(): void
    {
        ConsultationType::where('slug', 'introductory-call')->first()->markPublished();

        $this->get('/contact')->assertOk()->assertDontSee('not enabled in this review build')->assertSee('name="submission_key"', false)->assertSee(route('contact.store'), false);
        $this->get('/consultation')->assertOk()->assertSee('not reserved until BRIVIA confirms it')->assertSee('Introductory call')->assertSee('Asia/Beirut')->assertSee(route('consultation.store'), false);
    }

    public function test_consultation_without_published_types_explains_alternative(): void
    {
        $this->get('/consultation')->assertOk()->assertSee('Consultation booking is not available yet');
    }

    public function test_legal_pages_and_footer_links_follow_publication(): void
    {
        $this->get('/')->assertDontSee('href="'.route('privacy').'"', false);

        LegalPage::for(LegalPageKey::Privacy)->markPublished();

        $this->get('/privacy')->assertOk()->assertSee('Version draft-1');
        $this->get('/')->assertSee('href="'.route('privacy').'"', false);
    }

    public function test_seo_metadata_sitemap_and_robots(): void
    {
        Service::where('slug', 'new-products')->first()->markPublished();

        $this->get('/services/new-products')->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/services/new-products').'">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false); // non-production

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(url('/services/new-products'))->assertDontSee('redesign-modernization')->assertDontSee('/admin');

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

        $this->app->instance('env', 'production');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));
        $this->get('/services/new-products')->assertSee('<meta name="robots" content="index, follow">', false)->assertSee('application/ld+json', false);
    }

    public function test_faq_feature_is_removed_everywhere(): void
    {
        $this->assertFalse(Schema::hasTable('faqs'));
        $this->assertFalse(Schema::hasColumn('homepage_content', 'faq_heading'));
        $this->assertFalse(Route::has('admin.faqs.index'));

        foreach (['/', '/packages', '/services', '/about'] as $uri) {
            $this->get($uri)->assertOk()->assertDontSee('FAQ')->assertDontSee('Questions, answered');
        }

        $this->actingAsStaff(Role::Owner);
        $this->get('/admin/faqs')->assertNotFound();
        $this->get('/admin')->assertDontSee('FAQs');
        $this->get('/admin/homepage')->assertOk()->assertDontSee('faq_heading', false);
    }

    public function test_service_cards_are_shared_and_link_to_detail_pages(): void
    {
        Service::where('slug', 'new-products')->first()->markPublished();

        foreach (['/', '/services'] as $uri) {
            $this->get($uri)->assertOk()
                ->assertSee('Explore service<span class="sr-only">: New Products</span>', false)
                ->assertSee('href="'.route('services.show', 'new-products').'"', false);
        }
        $this->get('/services')->assertSee('Typical deliverables')->assertSee('Four ways we can help');
    }

    public function test_contact_tiles_show_only_configured_options_and_escape_values(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('Request a time')
            ->assertDontSee('mailto:', false)
            ->assertDontSee('tel:', false)
            ->assertDontSee('Chat with us');

        SiteSetting::current()->fill([
            'email' => 'hello@brivia.example', 'phone' => '+961 1 234 567',
            'whatsapp_url' => 'https://wa.me/9611234567', 'address' => 'Beirut <b>HQ</b>',
        ])->save();

        $this->get('/contact')->assertOk()
            ->assertSee('href="mailto:hello@brivia.example"', false)
            ->assertSee('hello@<wbr>brivia.<wbr>example', false)
            ->assertSee('href="tel:+9611234567"', false)
            ->assertSee('href="https://wa.me/9611234567"', false)->assertSee('Chat with us<span class="sr-only"> (opens in a new tab)</span>', false)
            ->assertSee('Beirut &lt;b&gt;HQ&lt;/b&gt;', false)
            ->assertDontSee('<b>HQ</b>', false);
    }

    public function test_home_hero_is_typography_led_without_illustration(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('The bridge from idea to product')
            ->assertDontSee('<svg class="h-auto w-full" viewBox="0 0 640 500"', false)
            ->assertSee('aria-label="Our strengths"', false);
    }

    public function test_navigation_marks_current_page_and_cta(): void
    {
        $this->get('/packages')->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('Book a consultation')
            ->assertSee('aria-controls="site-nav"', false);
    }
}
