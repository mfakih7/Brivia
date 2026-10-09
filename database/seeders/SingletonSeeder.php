<?php

namespace Database\Seeders;

use App\Enums\LegalPageKey;
use App\Models\CompanyProfile;
use App\Models\HomepageContent;
use App\Models\LegalPage;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SingletonSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SiteSetting::current();
        if (blank($settings->default_meta_title)) {
            $settings->fill([
                'business_name' => 'BRIVIA',
                'tagline' => 'The bridge from idea to product',
                'copyright_name' => 'BRIVIA',
                'default_meta_title' => 'BRIVIA — The bridge from idea to product',
                'default_meta_description' => 'We plan, build, and improve digital products with senior expertise.',
                'default_timezone' => 'Asia/Beirut',
                'social_links' => [],
                // Owner-configurable choices; these are enquiry categories, not quotes or promises.
                'budget_options' => ['Not sure yet', 'Under USD 5,000', 'USD 5,000 – 15,000', 'USD 15,000 – 40,000', 'Over USD 40,000'],
                'timeline_options' => ['As soon as possible', 'Within 1–3 months', 'In 3–6 months', 'Flexible / not sure yet'],
            ])->save();
        }

        $home = HomepageContent::current();
        if (blank($home->services_heading)) {
            $home->fill([
                'hero_eyebrow' => 'From idea to impact',
                'hero_headline' => 'The bridge from idea to product.',
                'hero_subtitle' => 'We plan, build, and improve digital products with senior expertise.',
                'primary_cta_label' => 'Discuss Your Project',
                'primary_cta_route' => 'contact',
                'secondary_cta_label' => 'Explore Our Work',
                'secondary_cta_route' => 'projects.index',
                'credibility_items' => ['Senior development', 'Product strategy', 'Project management'],
                'services_heading' => 'A clear path to your next product.',
                'projects_heading' => 'Ideas brought to life.',
                'packages_heading' => 'Choose your starting point.',
                'about_heading' => 'Built by experience. Driven by your vision.',
                'about_body' => "Two senior developers. Over 10 years of experience each.\n\nWe combine hands-on software delivery with project management experience to help you build the right product, the right way.",
                'process_heading' => 'How we work.',
                'process_steps' => [
                    ['title' => 'Discover', 'body' => 'We learn about your goals, users and constraints before suggesting solutions.'],
                    ['title' => 'Define', 'body' => 'We agree scope, priorities and a realistic plan in a written proposal.'],
                    ['title' => 'Design & Build', 'body' => 'We design and develop in reviewable steps so you can see progress early.'],
                    ['title' => 'Test & Launch', 'body' => 'We test with you, prepare the release and launch carefully.'],
                    ['title' => 'Support', 'body' => 'Where agreed, we stay involved to maintain and improve the product.'],
                ],
                'final_cta_heading' => 'Ready to bring your idea to life?',
                'final_cta_body' => "Let's talk about your project and find the best way forward.",
                'section_visibility' => array_fill_keys(array_keys(HomepageContent::SECTIONS), true),
            ])->save();
        }

        $profile = CompanyProfile::current();
        if (blank($profile->story)) {
            $profile->fill([
                'public_intro' => 'BRIVIA combines two words — Bridge and Via. We are the bridge from idea to product.',
                'story' => "BRIVIA was founded by two senior developers, each with over ten years of experience delivering software in Lebanon and internationally.\n\nAlongside engineering, we bring hands-on project management experience, so planning, communication and delivery are part of the work from day one.",
                'mission' => 'We plan, build, and improve digital products with senior expertise.',
                'values' => [],
            ])->save();
        }

        foreach (LegalPageKey::cases() as $key) {
            if (! LegalPage::for($key)) {
                $page = new LegalPage([
                    'title' => $key->label(),
                    'version_label' => 'draft-1',
                    'body' => $this->legalOutline($key),
                ]);
                $page->forceFill(['key' => $key, 'status' => 'draft', 'is_placeholder' => true])->save();
            }
        }
    }

    private function legalOutline(LegalPageKey $key): string
    {
        $intro = "DRAFT OUTLINE — this text is a placeholder and must be reviewed and approved by the owner (and legal counsel where appropriate) before publication.\n\n";

        return $intro.match ($key) {
            LegalPageKey::Privacy => implode("\n\n", [
                '## Who we are', 'BRIVIA — contact details to be confirmed by the owner.',
                '## What we collect', "- Details you submit through the contact or consultation forms (name, email, optional phone and company, your message and preferred times).\n- The version of this policy you accepted and when.",
                '## How we use it', 'To review and respond to your enquiry or consultation request. We do not sell your information.',
                '## Retention', 'Retention periods are pending owner approval.',
                '## Your choices', 'How to request access or deletion — to be confirmed by the owner.',
            ]),
            LegalPageKey::Terms => implode("\n\n", [
                '## Using this website', 'Terms to be confirmed by the owner.',
                '## Enquiries and proposals', 'Submitting an enquiry or selecting a package is not a purchase. Scope, pricing and timelines are confirmed only in a written proposal.',
                '## Consultation requests', 'Preferred times are requests, not reservations, until BRIVIA confirms them.',
            ]),
        };
    }
}
