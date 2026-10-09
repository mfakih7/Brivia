<?php

namespace Database\Seeders;

use App\Enums\IconKey;
use App\Enums\PriceMode;
use App\Enums\PublicationStatus;
use App\Models\ConsultationType;
use App\Models\Package;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;

/**
 * Starter content from the specification, always created as DRAFT and never overwriting edits.
 * Packages use quote mode (no invented prices). Founders are clearly marked placeholders.
 */
class DraftContentSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['new-products', 'New Products', IconKey::Lightbulb,
                'From concept to launch, we help you plan and build a new website or web application.',
                "For founders and businesses with a new idea that needs a clear plan and senior engineering.\n\nWe start with discovery, agree an MVP scope in writing, then design, build, test and launch it with you. Examples below describe typical work; your deliverables are confirmed in a proposal.",
                ['Discovery and requirements', 'Website or web application', 'Integrations with the services you use', 'An agreed MVP scope', 'Testing and launch'],
                ['Discovery conversation', 'Written scope and proposal', 'Design and build in reviewable steps', 'Testing and launch']],
            ['redesign-modernization', 'Redesign & Modernization', IconKey::Layers,
                'Modernize, improve, and scale your existing product for what comes next.',
                "For organizations with an existing product that is hard to change, slow, or due for a refresh.\n\nWe assess what you have, then propose a practical path: a UX redesign, code restructuring, performance work or a migration plan. We never ask for production access in a public form.",
                ['Assessment of the current product', 'UX redesign', 'Code restructuring', 'Performance improvements', 'Migration plan'],
                ['Assessment conversation', 'Findings and options', 'Agreed improvement plan', 'Delivery in stages']],
            ['technical-consulting', 'Technical Consulting', IconKey::Chat,
                'Senior guidance on architecture, technology and product decisions.',
                "For teams that need an experienced second opinion before committing to a direction.\n\nWe review architecture, code or project plans and give written, practical recommendations.",
                ['Architecture review', 'Technology advice', 'Code or project assessment', 'Written recommendations'],
                ['Consultation', 'Review', 'Written recommendations', 'Follow-up discussion']],
            ['delivery-support', 'Delivery & Support', IconKey::Gear,
                'We coordinate delivery and support your product with a focus on long-term success.',
                "For products that need structured delivery: clear requirements, a roadmap and quality assurance.\n\nMaintenance is included only where it is explicitly agreed.",
                ['Requirements', 'Roadmap', 'Delivery coordination', 'Quality assurance', 'Launch', 'Agreed maintenance'],
                ['Planning', 'Roadmap agreement', 'Coordinated delivery', 'Agreed support']],
        ];

        foreach ($services as $i => [$slug, $title, $icon, $summary, $body, $deliverables, $steps]) {
            $service = Service::firstWhere('slug', $slug) ?? new Service;
            if (! $service->exists) {
                $service->fill(compact('slug', 'title', 'summary', 'body', 'deliverables') + [
                    'icon_key' => $icon, 'engagement_steps' => $steps, 'sort_order' => ($i + 1) * 10,
                ]);
                $service->forceFill(['status' => PublicationStatus::Draft])->save();
            }
        }

        $packages = [
            ['business-website', 'Business Website', IconKey::Monitor, 'A modern website tailored to your business goals.',
                ['Strategy and planning', 'Modern design and development', 'SEO best practices', 'Content management where agreed'],
                ['Hosting and paid third-party services', 'Ongoing maintenance unless agreed'], ['new-products']],
            ['mvp-custom-application', 'MVP / Custom Application', IconKey::Cube, 'A tailored web application built around your requirements.',
                ['Product discovery', 'Scalable architecture', 'Full development and testing', 'Launch support'],
                ['App-store submission', 'Indefinite maintenance', 'Paid third-party services'], ['new-products', 'delivery-support']],
            ['redesign-modernization', 'Redesign & Modernization', IconKey::Layers, 'Improve an existing product without starting over.',
                ['Assessment', 'UX redesign', 'Code restructuring', 'Performance work'],
                ['Source migration unless agreed', 'Hosting'], ['redesign-modernization']],
            ['consulting-session', 'Consulting Session', IconKey::Users, 'Senior advice for your product and technology decisions.',
                ['Technical architecture', 'Product strategy', 'Project planning and reviews', 'Written recommendations'],
                ['Implementation work'], ['technical-consulting']],
        ];

        foreach ($packages as $i => [$slug, $name, $icon, $summary, $features, $exclusions, $serviceSlugs]) {
            if (Package::where('slug', $slug)->exists()) {
                continue;
            }
            $package = new Package(compact('slug', 'name', 'summary', 'features', 'exclusions') + [
                'icon_key' => $icon, 'price_mode' => PriceMode::Quote, 'currency' => 'USD', 'sort_order' => ($i + 1) * 10,
            ]);
            $package->forceFill(['status' => PublicationStatus::Draft])->save();
            $package->services()->sync(Service::whereIn('slug', $serviceSlugs)->pluck('id'));
        }

        foreach ([['Business website', 'business-website'], ['Web application', 'web-application'], ['Dashboard', 'dashboard'], ['Modernization', 'modernization']] as $i => [$name, $slug]) {
            ProjectCategory::firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => ($i + 1) * 10]);
        }

        foreach ([['introductory-call', 'Introductory call', 30, 'A short conversation about your idea or product and how we might help.'],
            ['technical-consultation', 'Technical consultation', 60, 'A focused session on architecture, technology or delivery questions.']] as $i => [$slug, $name, $minutes, $description]) {
            if (! ConsultationType::where('slug', $slug)->exists()) {
                // Quote mode until the owner confirms whether consultations are free or paid.
                $type = new ConsultationType(compact('slug', 'name', 'description') + [
                    'duration_minutes' => $minutes, 'pricing_mode' => 'quote', 'currency' => 'USD', 'sort_order' => ($i + 1) * 10,
                ]);
                $type->forceFill(['status' => PublicationStatus::Draft])->save();
            }
        }

        if (! TeamMember::exists()) {
            foreach ([1, 2] as $n) {
                $member = new TeamMember([
                    'name' => "Founder {$n} (placeholder)",
                    'role_title' => 'Co-founder — role to be confirmed',
                    'biography' => 'Placeholder biography. Replace with the approved founder biography before publishing.',
                    'skills' => [],
                    'years_experience' => null,
                    'social_links' => [],
                    'sort_order' => $n * 10,
                ]);
                $member->forceFill(['status' => PublicationStatus::Draft, 'is_placeholder' => true])->save();
            }
        }

    }
}
