<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $contact = function (Blueprint $table): void {
            $table->string('public_reference', 20)->unique();
            $table->uuid('submission_key')->unique();
            $table->char('payload_hash', 64);
            $table->string('name', 120);
            $table->string('email', 254)->index();
            $table->string('phone', 40)->nullable();
            $table->string('company', 160)->nullable();
        };

        Schema::create('enquiries', function (Blueprint $table) use ($contact) {
            $table->id();
            $contact($table);
            $table->string('enquiry_type', 32);
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('context_snapshot')->nullable();
            $table->string('budget', 120)->nullable();
            $table->string('timeline', 120)->nullable();
            $table->text('message');
            $table->string('status', 16)->default('new');
            $table->foreignId('assigned_to')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->string('policy_version', 40);
            $table->timestamp('privacy_accepted_at');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('enquiry_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('from_value', 120)->nullable();
            $table->string('to_value', 120)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('appointments', function (Blueprint $table) use ($contact) {
            $table->id();
            $contact($table);
            $table->foreignId('consultation_type_id')->constrained()->restrictOnDelete();
            $table->string('type_snapshot', 120);
            $table->unsignedSmallInteger('duration_minutes');
            $table->text('summary');
            $table->dateTime('preferred_at_utc');
            $table->dateTime('alternate_at_utc')->nullable();
            $table->string('requested_timezone', 64);
            $table->dateTime('confirmed_start_at_utc')->nullable();
            $table->dateTime('confirmed_end_at_utc')->nullable();
            $table->string('status', 16)->default('requested');
            $table->foreignId('assigned_to')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('meeting_url', 2048)->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->unsignedInteger('schedule_version')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->string('policy_version', 40);
            $table->timestamp('privacy_accepted_at');
            $table->timestamps();
            $table->index(['status', 'confirmed_start_at_utc']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('appointment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40);
            $table->json('previous')->nullable();
            $table->json('new')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('record_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('resource_type', 60);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('changed_fields')->nullable();
            $table->string('request_id', 64)->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['resource_type', 'resource_id']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 191)->unique();
            $table->foreignId('enquiry_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 48);
            $table->string('recipient_email', 254);
            $table->json('payload');
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        foreach (['notification_deliveries', 'audit_logs', 'record_notes', 'appointment_events', 'appointments', 'enquiry_events', 'enquiries'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
