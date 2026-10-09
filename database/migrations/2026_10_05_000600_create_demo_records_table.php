<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry of development-only demo content (see DemoContentSeeder). Lets cleanup remove
 * exactly the records it created and restore singleton fields it filled. Empty in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_records', function (Blueprint $table) {
            $table->id();
            $table->string('demo_key', 120)->unique();
            $table->string('record_type', 60);
            $table->unsignedBigInteger('record_id');
            $table->json('original_values')->nullable();
            $table->json('demo_values')->nullable();
            $table->timestamps();
            $table->index(['record_type', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_records');
    }
};
