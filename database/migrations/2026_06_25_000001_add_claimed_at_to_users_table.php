<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an explicit `claimed_at` timestamp to model the guest vs. claimed account
 * state (#8). A guest auto-user (D1) has `claimed_at = null`; a real/claimed
 * account has it set. This replaces the implicit `@guest.local` email-suffix
 * heuristic: the placeholder email stays as a unique placeholder, but the guest
 * concept is now derived from `claimed_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('claimed_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('claimed_at');
        });
    }
};
