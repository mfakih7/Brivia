<?php

namespace Database\Seeders;

use App\Enums\ConsultationPricingMode;
use App\Enums\IconKey;
use App\Enums\MediaRole;
use App\Enums\PriceMode;
use App\Enums\WorkOrigin;
use App\Models\CompanyProfile;
use App\Models\ConsultationType;
use App\Models\DemoRecord;
use App\Models\Package;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectMedia;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Support\DemoImageGenerator;
use App\Support\ImageProcessor;
use App\Support\PublicContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * DEVELOPMENT-ONLY demo content for reviewing the public website.
 *
 *   C:\php83\php.exe artisan db:seed --class=DemoContentSeeder
 *   C:\php83\php.exe artisan brivia:demo-content:remove      (guarded cleanup)
 *
 * - Runs only when APP_ENV is local or testing; aborts everywhere else.
 * - Never called from DatabaseSeeder or deployment scripts.
 * - Every record/image it creates is registered in `demo_records`; re-running skips existing
 *   demo records and never overwrites real content (slug collisions are skipped, not replaced).
 * - Singleton settings are filled ONLY where blank; originals are recorded for cleanup.
 * - Fictional work is labelled "Sample concept"; prior-experience entries and founders are
 *   explicit placeholders. No real clients, testimonials, metrics or achievements.
 * - Creates no users, sends no email and schedules no notifications.
 */
class DemoContentSeeder extends Seeder
{
    private const DEMO_PRICE_LABEL = '(illustrative demo price)';

    private array $skipped = [];

    private array $created = [];

    public function __construct(private readonly ImageProcessor $images, private readonly DemoImageGenerator $generator) {}

    public function run(): void
    {
        if (! DemoRecord::isAllowedEnvironment()) {
            throw new RuntimeException('DemoContentSeeder runs only when APP_ENV is local or testing. Aborted; nothing was changed.');
        }

        $services = $this->services();
        $this->packages($services);
        $categories = $this->categories();
        $this->projects($categories, $services);
        $this->team();
        $this->consultationTypes();
        $this->fillBlankFields('site_settings', SiteSetting::current(), [
            'email' => 'hello@brivia.example',
            'phone' => '+1 555 0100',
            'whatsapp_url' => 'https://wa.me/15550100',
            'address' => 'Sample address — replace in Admin → Settings',
            'service_coverage' => 'Sample coverage text: clients in Lebanon and internationally (remote).',
            'social_links' => ['linkedin' => 'https://example.com/brivia-linkedin-sample', 'github' => 'https://example.com/brivia-github-sample'],
        ]);
        $this->fillBlankFields('company_profile', CompanyProfile::current(), [
            'values' => [
                ['title' => 'Clarity first', 'body' => 'Sample value: plain-language plans, written scope and no surprises.'],
                ['title' => 'Senior ownership', 'body' => 'Sample value: the people you talk to are the people building your product.'],
                ['title' => 'Honest delivery', 'body' => 'Sample value: realistic timelines and transparent trade-offs.'],
            ],
        ]);

        PublicContentCache::flush();

        $this->command?->info('Demo content: '.count($this->created).' item(s) created, '.count($this->skipped).' skipped.');
        foreach ($this->skipped as $reason) {
            $this->command?->line('  skipped: '.$reason);
        }
        $this->command?->warn('Founders and the earlier-experience project are PLACEHOLDERS; projects marked "Sample concept" are fictional; prices are illustrative.');
    }

    /** @return array<string, Service> */
    private function services(): array
    {
        $rows = [
            ['new-products', 'New Products', IconKey::Lightbulb, 'From concept to launch, we help you plan and build a new website or web application.',
                "For founders and businesses with a new idea that needs a clear plan and senior engineering.\n\nWe start with a discovery conversation, agree an MVP scope in writing, then design, build, test and launch it with you.\n\n## Good fit if you\n- Have an idea and need help turning it into a realistic first version\n- Want senior developers involved from the first conversation\n- Prefer a written scope before work starts",
                ['Discovery and requirements', 'Website or web application', 'Integrations with the services you already use', 'An agreed MVP scope', 'Testing and launch support'],
                ['Discovery conversation', 'Written scope and proposal', 'Design and build in reviewable steps', 'Testing and launch']],
            ['redesign-modernization', 'Redesign & Modernization', IconKey::Layers, 'Modernize, improve, and scale your existing product for what comes next.',
                "For organizations with an existing product that is slow, hard to change, or due for a refresh.\n\nWe assess what you have and propose a practical path: UX redesign, code restructuring, performance work or a migration plan.\n\nWe never ask for passwords or production access through a public form.",
                ['Assessment of the current product', 'UX redesign', 'Code restructuring', 'Performance improvements', 'Migration plan'],
                ['Assessment conversation', 'Findings and options', 'Agreed improvement plan', 'Delivery in stages']],
            ['technical-consulting', 'Technical Consulting', IconKey::Chat, 'Senior guidance on architecture, technology and product decisions.',
                "For teams who want an experienced second opinion before committing to a direction.\n\nWe review architecture, code or delivery plans and give written, practical recommendations.",
                ['Architecture review', 'Technology advice', 'Code or project assessment', 'Written recommendations'],
                ['Consultation', 'Review', 'Written recommendations', 'Follow-up discussion']],
            ['delivery-support', 'Delivery & Support', IconKey::Gear, 'We coordinate delivery and support your product with a focus on long-term success.',
                "For products that need structured delivery: clear requirements, a roadmap and quality assurance.\n\nOngoing maintenance is included only where it is explicitly agreed.",
                ['Requirements and roadmap', 'Delivery coordination', 'Quality assurance', 'Launch', 'Agreed maintenance'],
                ['Planning', 'Roadmap agreement', 'Coordinated delivery', 'Agreed support']],
        ];

        $services = [];
        foreach ($rows as $i => [$key, $title, $icon, $summary, $body, $deliverables, $steps]) {
            $services[$key] = $this->firstOrCreateDemo("service:{$key}", 'service', Service::class, "demo-{$key}", fn () => new Service([
                'title' => $title, 'slug' => "demo-{$key}", 'summary' => $summary, 'body' => $body,
                'deliverables' => $deliverables, 'engagement_steps' => $steps, 'icon_key' => $icon, 'sort_order' => ($i + 1) * 10,
                'meta_description' => mb_substr($summary, 0, 160),
            ]), publish: true);
        }

        return array_filter($services);
    }

    private function packages(array $services): void
    {
        $rows = [
            ['business-website', 'Business Website', IconKey::Monitor, 'A modern website tailored to your business goals. Demo pricing shown for local review only.',
                ['Strategy and planning', 'Modern design and development', 'SEO best practices', 'Content management where agreed'],
                ['Hosting and paid third-party services', 'Ongoing maintenance unless agreed'], PriceMode::StartingFrom, 2500, ['new-products'], false],
            ['mvp-custom-application', 'MVP / Custom Application', IconKey::Cube, 'A tailored web application built around your requirements. Demo pricing shown for local review only.',
                ['Product discovery', 'Maintainable architecture', 'Full development and testing', 'Launch support'],
                ['App-store submission', 'Indefinite maintenance', 'Paid third-party services'], PriceMode::StartingFrom, 9000, ['new-products', 'delivery-support'], true],
            ['redesign-modernization', 'Redesign & Modernization', IconKey::Layers, 'Improve an existing product without starting over.',
                ['Assessment', 'UX redesign', 'Code restructuring', 'Performance work'],
                ['Source migration unless agreed', 'Hosting'], PriceMode::Quote, null, ['redesign-modernization'], false],
            ['consulting-session', 'Consulting Session', IconKey::Users, 'Senior advice for your product and technology decisions. Demo pricing shown for local review only.',
                ['Technical architecture', 'Product strategy', 'Project planning and reviews', 'Written recommendations'],
                ['Implementation work'], PriceMode::Fixed, 150, ['technical-consulting'], false],
        ];

        foreach ($rows as $i => [$key, $name, $icon, $summary, $features, $exclusions, $mode, $amount, $serviceKeys, $featured]) {
            $package = $this->firstOrCreateDemo("package:{$key}", 'package', Package::class, "demo-{$key}", fn () => new Package([
                'name' => $name, 'slug' => "demo-{$key}", 'summary' => $summary, 'features' => $features, 'exclusions' => $exclusions,
                'price_mode' => $mode, 'price_amount' => $amount, 'currency' => 'USD',
                'billing_label' => $amount === null ? null : self::DEMO_PRICE_LABEL, 'icon_key' => $icon,
                'is_featured' => $featured, 'sort_order' => ($i + 1) * 10,
            ]), publish: true);

            if ($package?->wasRecentlyCreated) {
                $package->services()->sync(collect($serviceKeys)->map(fn ($k) => ($services[$k] ?? null)?->id)->filter()->all());
            }
        }
    }

    /** Reuses existing categories by slug (read-only); creates and tracks any that are missing. */
    private function categories(): array
    {
        $categories = [];
        foreach ([['business-website', 'Business website'], ['web-application', 'Web application'], ['dashboard', 'Dashboard'], ['modernization', 'Modernization']] as $i => [$slug, $name]) {
            $existing = ProjectCategory::where('slug', $slug)->first();
            $categories[$slug] = $existing ?? $this->firstOrCreateDemo("project_category:{$slug}", 'project_category', ProjectCategory::class, $slug,
                fn () => new ProjectCategory(['name' => $name, 'slug' => $slug, 'sort_order' => ($i + 1) * 10]));
        }

        return $categories;
    }

    private function projects(array $categories, array $services): void
    {
        $approach = "We started from the main user goals and kept the first version deliberately small.\n\n- Short discovery workshop\n- Clickable layout prototype\n- Build in reviewable two-week steps";
        $rows = [
            ['north-business-website', 'North — business website', 'business-website', WorkOrigin::Concept, 'website', [12, 54, 120], true,
                'A modern marketing website concept for a growing service business.',
                'A fictional service company needs a clear website that explains its offer and turns visitors into enquiries.',
                'Sample concept prepared by BRIVIA to illustrate a business website project.',
                'A fast, accessible site with clear service pages, a simple enquiry flow and editable content.',
                ['Laravel', 'Blade', 'Tailwind CSS'], ['new-products']],
            ['pulse-operations-dashboard', 'Pulse — operations dashboard', 'dashboard', WorkOrigin::Concept, 'dashboard', [0, 90, 140], true,
                'A dashboard concept that turns operational data into clear daily decisions.',
                'A fictional operations team tracks work across spreadsheets and cannot see priorities at a glance.',
                'Sample concept prepared by BRIVIA to illustrate a dashboard project.',
                'A role-based dashboard with filtered lists, simple charts and a weekly summary view.',
                ['Laravel', 'MySQL', 'Charts'], ['new-products', 'delivery-support']],
            ['harbor-legacy-refresh', 'Harbor — legacy system refresh', 'modernization', WorkOrigin::Concept, 'legacy', [20, 40, 90], false,
                'A concept showing how an outdated internal tool could be restructured and redesigned.',
                'A fictional internal tool has grown over many years; small changes are slow and risky.',
                'Sample concept prepared by BRIVIA to illustrate a modernization assessment.',
                'A staged plan: stabilise, restructure the riskiest modules, then refresh the interface screen by screen.',
                ['PHP', 'Laravel', 'Accessibility'], ['redesign-modernization']],
            ['ledger-booking-app', 'Ledger — booking web application', 'web-application', WorkOrigin::Concept, 'mobile', [10, 70, 110], false,
                'A booking application concept for a small service business with mobile-first scheduling.',
                'A fictional studio manages bookings by phone and messages, which leads to double bookings.',
                'Sample concept prepared by BRIVIA to illustrate a custom web application.',
                'A mobile-friendly booking flow with staff confirmation and a simple admin calendar view.',
                ['Laravel', 'MySQL', 'Progressive enhancement'], ['new-products']],
            ['atlas-field-portal', 'Atlas — field team portal', 'web-application', WorkOrigin::Concept, 'mobile', [30, 60, 130], false,
                'A portal concept that helps a field team submit visit reports from their phones.',
                'A fictional field team writes reports on paper and re-types them in the office.',
                'Sample concept prepared by BRIVIA to illustrate a responsive internal portal.',
                'Short mobile forms with offline-tolerant drafts and a reviewer queue in the office.',
                ['Laravel', 'Responsive design', 'Role permissions'], ['new-products', 'delivery-support']],
            ['earlier-experience-placeholder', 'Earlier experience example (placeholder)', 'web-application', WorkOrigin::FounderExperience, 'dashboard', [40, 50, 100], false,
                'PLACEHOLDER — replace with a real, permitted example from the founders’ earlier careers, or delete.',
                'PLACEHOLDER: describe the context of a real project from before BRIVIA, only with permission.',
                'PLACEHOLDER: describe the founder’s actual role and contribution.',
                'PLACEHOLDER: describe the solution in plain language. Do not add client names, metrics or outcomes unless they are approved and supported.',
                ['Placeholder'], ['delivery-support']],
        ];

        foreach ($rows as $i => [$key, $title, $category, $origin, $style, $tint, $featured, $summary, $problem, $contribution, $solution, $tech, $serviceKeys]) {
            $project = $this->firstOrCreateDemo("project:{$key}", 'project', Project::class, "demo-{$key}", fn () => new Project([
                'title' => $title, 'slug' => "demo-{$key}", 'category_id' => $categories[$category]?->id, 'summary' => $summary,
                'work_origin' => $origin, 'problem' => $problem, 'contribution' => $contribution, 'approach' => $approach,
                'solution' => $solution, 'outcomes' => null, 'technologies' => $tech, 'is_featured' => $featured,
                'permission_confirmed' => true, 'sort_order' => ($i + 1) * 10,
            ]));

            if (! $project?->wasRecentlyCreated) {
                continue;
            }

            $project->services()->sync(collect($serviceKeys)->map(fn ($k) => ($services[$k] ?? null)?->id)->filter()->all());

            $this->attachImage($project, "media:{$key}:cover", MediaRole::Cover, $style, $tint, $i, "Abstract illustration for the {$title} demo", null);
            foreach ([1, 2] as $g) {
                $this->attachImage($project, "media:{$key}:gallery{$g}", MediaRole::Gallery, $g === 1 ? $style : 'website', $tint, $i + $g * 3,
                    "Abstract interface illustration {$g} for the {$title} demo", "Illustration {$g} (demo image, not a real screenshot)");
            }

            // Spread publication dates so newest-first ordering is visible.
            $project->forceFill(['published_at' => now()->subDays(10 - $i)])->save();
            $project->markPublished();
        }
    }

    private function attachImage(Project $project, string $key, MediaRole $role, string $style, array $tint, int $seed, string $alt, ?string $caption): void
    {
        if (DemoRecord::where('demo_key', $key)->exists()) {
            return;
        }

        $media = $this->images->store($this->generator->make($style, $tint, $seed), $role === MediaRole::Cover ? 'cover' : 'gallery', $alt);
        DemoRecord::track($key, 'media', $media->id);

        $link = new ProjectMedia(['caption' => $caption, 'sort_order' => $seed * 10]);
        $link->forceFill(['project_id' => $project->id, 'media_id' => $media->id, 'role' => $role])->save();
    }

    private function team(): void
    {
        $founders = [
            'a' => ['Founder A', ['Laravel', 'PHP', 'Architecture', 'Project management']],
            'b' => ['Founder B', ['Web applications', 'Databases', 'Delivery planning', 'Quality assurance']],
        ];

        foreach ($founders as $key => [$name, $skills]) {
            $this->firstOrCreateDemo("team_member:{$key}", 'team_member', TeamMember::class, null, function () use ($name, $skills, $key) {
                $member = new TeamMember([
                    'name' => "{$name} (placeholder name)",
                    'role_title' => 'Co-founder · Senior developer (placeholder)',
                    'biography' => "PLACEHOLDER biography for local review. Replace with the approved biography of the real founder.\n\nThe approved positioning is: two senior developers, each with over ten years of experience in Lebanon and internationally, combining software delivery with project management.",
                    'skills' => $skills,
                    'years_experience' => 10,
                    'social_links' => [],
                    'sort_order' => $key === 'a' ? 10 : 20,
                ]);
                $member->forceFill(['is_placeholder' => true]);

                return $member;
            }, publish: true);
        }
    }

    private function consultationTypes(): void
    {
        $rows = [
            ['introductory-call', 'Introductory call', 30, ConsultationPricingMode::Free, null, 'A short conversation about your idea or product and how we might help. (Demo consultation type.)'],
            ['technical-consultation', 'Technical consultation', 60, ConsultationPricingMode::Fixed, 100, 'A focused session on architecture, technology or delivery questions. (Demo type; fee is illustrative.)'],
        ];

        foreach ($rows as $i => [$key, $name, $minutes, $mode, $amount, $description]) {
            $this->firstOrCreateDemo("consultation_type:{$key}", 'consultation_type', ConsultationType::class, "demo-{$key}", fn () => new ConsultationType([
                'name' => $name, 'slug' => "demo-{$key}", 'description' => $description, 'duration_minutes' => $minutes,
                'pricing_mode' => $mode, 'amount' => $amount, 'currency' => 'USD', 'sort_order' => ($i + 1) * 10,
            ]), publish: true);
        }
    }

    /**
     * Creates a tracked demo record unless one already exists. If a NON-demo record already
     * owns the slug it is left untouched and the demo item is skipped.
     */
    private function firstOrCreateDemo(string $key, string $type, string $class, ?string $slug, callable $make, bool $publish = false): ?Model
    {
        if ($tracked = DemoRecord::where('demo_key', $key)->first()) {
            return $tracked->model();
        }

        if ($slug !== null && $class::where('slug', $slug)->exists()) {
            $this->skipped[] = "{$key} (slug '{$slug}' is already used by non-demo content)";

            return null;
        }

        $model = $make();
        $model->save();
        DemoRecord::track($key, $type, $model->getKey());
        $this->created[] = $key;

        if ($publish) {
            $model->markPublished();
        }

        return $model;
    }

    /** Fills only blank singleton fields and records the originals so cleanup can restore them. */
    private function fillBlankFields(string $type, Model $singleton, array $values): void
    {
        $key = "{$type}:fields";
        if (DemoRecord::where('demo_key', $key)->exists()) {
            return;
        }

        $filled = array_filter($values, fn ($value, $field) => blank($singleton->{$field}), ARRAY_FILTER_USE_BOTH);
        if ($filled === []) {
            $this->skipped[] = "{$key} (all fields already have real values)";

            return;
        }

        $original = collect($filled)->mapWithKeys(fn ($v, $field) => [$field => $singleton->{$field}])->all();
        $singleton->fill($filled)->save();
        DemoRecord::track($key, $type, $singleton->getKey(), $original, $filled);
        $this->created[] = $key;
    }
}
