<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $seo = function (Blueprint $table): void {
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->foreignId('og_media_id')->nullable()->constrained('media')->restrictOnDelete();
        };

        $publication = function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->index(['status', 'published_at']);
        };

        Schema::create('services', function (Blueprint $table) use ($seo, $publication) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 400);
            $table->text('body')->nullable();
            $table->json('deliverables');
            $table->json('engagement_steps');
            $table->string('icon_key', 32);
            $publication($table);
            $seo($table);
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) use ($seo, $publication) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('summary', 400);
            $table->json('features');
            $table->json('exclusions');
            $table->string('price_mode', 16)->default('quote');
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->string('billing_label', 60)->nullable();
            $table->string('icon_key', 32)->default('monitor');
            $table->boolean('is_featured')->default(false);
            $publication($table);
            $seo($table);
            $table->timestamps();
        });

        Schema::create('package_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unique(['package_id', 'service_id']);
        });

        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) use ($seo, $publication) {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->foreignId('category_id')->constrained('project_categories')->restrictOnDelete();
            $table->string('summary', 500);
            $table->string('client_display_name', 160)->nullable();
            $table->string('work_origin', 32);
            $table->text('contribution')->nullable();
            $table->text('problem')->nullable();
            $table->text('approach')->nullable();
            $table->text('solution')->nullable();
            $table->text('outcomes')->nullable();
            $table->json('technologies');
            $table->string('website_url', 2048)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('permission_confirmed')->default(false);
            $publication($table);
            $seo($table);
            $table->timestamps();
        });

        Schema::create('project_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unique(['project_id', 'service_id']);
        });

        Schema::create('project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('role', 16);
            $table->string('caption', 240)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'media_id']);
            $table->index(['project_id', 'role', 'sort_order']);
        });

        Schema::create('team_members', function (Blueprint $table) use ($publication) {
            $table->id();
            $table->string('name', 120);
            $table->string('role_title', 160);
            $table->text('biography')->nullable();
            $table->json('skills');
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->foreignId('portrait_media_id')->nullable()->constrained('media')->restrictOnDelete();
            $table->json('social_links');
            $table->boolean('is_placeholder')->default(false);
            $publication($table);
            $table->timestamps();
        });

        Schema::create('company_profile', function (Blueprint $table) {
            $table->id();
            $table->text('public_intro')->nullable();
            $table->text('story')->nullable();
            $table->text('mission')->nullable();
            $table->json('values');
            $table->timestamps();
        });

        Schema::create('homepage_content', function (Blueprint $table) {
            $table->id();
            $table->string('hero_eyebrow', 80)->nullable();
            $table->string('hero_headline', 160);
            $table->string('hero_subtitle', 300)->nullable();
            $table->string('primary_cta_label', 40);
            $table->string('primary_cta_route', 64);
            $table->string('secondary_cta_label', 40)->nullable();
            $table->string('secondary_cta_route', 64)->nullable();
            $table->json('credibility_items');
            $table->string('services_heading', 160)->nullable();
            $table->string('projects_heading', 160)->nullable();
            $table->string('packages_heading', 160)->nullable();
            $table->string('about_heading', 160)->nullable();
            $table->text('about_body')->nullable();
            $table->string('process_heading', 160)->nullable();
            $table->json('process_steps');
            $table->string('faq_heading', 160)->nullable();
            $table->string('final_cta_heading', 160)->nullable();
            $table->string('final_cta_body', 300)->nullable();
            $table->json('section_visibility');
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) use ($publication) {
            $table->id();
            $table->string('question', 240);
            $table->text('answer');
            $table->string('placement', 16)->default('both');
            $publication($table);
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 120);
            $table->string('tagline', 160)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('service_coverage', 300)->nullable();
            $table->string('whatsapp_url', 2048)->nullable();
            $table->json('social_links');
            $table->json('budget_options');
            $table->json('timeline_options');
            $table->string('default_meta_title', 70)->nullable();
            $table->string('default_meta_description', 160)->nullable();
            $table->string('default_timezone', 64)->default('Asia/Beirut');
            $table->string('copyright_name', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 16)->unique();
            $table->string('title', 160);
            $table->longText('body');
            $table->string('version_label', 40);
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_placeholder')->default(true);
            $table->timestamps();
        });

        Schema::create('consultation_types', function (Blueprint $table) use ($publication) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 500);
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('pricing_mode', 16)->default('free');
            $table->decimal('amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $publication($table);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'consultation_types', 'legal_pages', 'site_settings', 'faqs', 'homepage_content', 'company_profile',
            'team_members', 'project_media', 'project_service', 'projects', 'project_categories',
            'package_service', 'packages', 'services',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
