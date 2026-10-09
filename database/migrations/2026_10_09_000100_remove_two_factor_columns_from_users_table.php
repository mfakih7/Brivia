<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner decision (2026-10-09): staff sign in with email + password only. Removes the stored
 * two-factor secrets and recovery codes (no longer used) without touching any other user data,
 * so previously enrolled accounts keep their email, password, role and status.
 */
return new class extends Migration
{
    private const COLUMNS = ['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_used_step'];

    public function up(): void
    {
        $existing = array_values(array_filter(self::COLUMNS, fn (string $column) => Schema::hasColumn('users', $column)));

        if ($existing !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('is_active');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->unsignedBigInteger('two_factor_last_used_step')->nullable()->after('two_factor_confirmed_at');
        });
    }
};
