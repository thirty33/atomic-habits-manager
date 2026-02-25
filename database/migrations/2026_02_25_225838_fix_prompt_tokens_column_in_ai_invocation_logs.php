<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_invocation_logs', function (Blueprint $table) {
            $table->unsignedInteger('prompt_tokens')->nullable()->change();
            $table->unsignedInteger('completion_tokens')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_invocation_logs', function (Blueprint $table) {
            $table->smallInteger('prompt_tokens')->nullable()->change();
            $table->smallInteger('completion_tokens')->nullable()->change();
        });
    }
};
