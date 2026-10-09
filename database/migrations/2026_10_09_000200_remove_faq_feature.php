<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner decision (2026-10-09): the FAQ feature is removed from the website and admin.
 * Drops only FAQ-specific storage; all other data is preserved. Historical audit entries that
 * mention FAQs are kept as history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demo_records')) {
            DB::table('demo_records')->where('record_type', 'faq')->delete();
        }

        Schema::dropIfExists('faqs');

        if (Schema::hasColumn('homepage_content', 'faq_heading')) {
            Schema::table('homepage_content', fn (Blueprint $table) => $table->dropColumn('faq_heading'));
        }

        // Remove the obsolete "faq" key from the homepage section-visibility map.
        foreach (DB::table('homepage_content')->get(['id', 'section_visibility']) as $row) {
            $map = json_decode((string) $row->section_visibility, true);
            if (is_array($map) && array_key_exists('faq', $map)) {
                unset($map['faq']);
                DB::table('homepage_content')->where('id', $row->id)->update(['section_visibility' => json_encode($map)]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question', 240);
                $table->text('answer');
                $table->string('placement', 16)->default('both');
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->string('status', 16)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->index(['status', 'published_at']);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('homepage_content', 'faq_heading')) {
            Schema::table('homepage_content', fn (Blueprint $table) => $table->string('faq_heading', 160)->nullable());
        }
    }
};
