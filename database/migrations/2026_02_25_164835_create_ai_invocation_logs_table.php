<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_invocation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('agent', 100);
            $table->text('prompt');
            $table->text('response')->nullable();
            $table->text('tool_calls')->nullable();
            $table->unsignedSmallInteger('prompt_tokens')->nullable();
            $table->unsignedSmallInteger('completion_tokens')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_invocation_logs');
    }
};
