<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_event_outbox', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('event_id', 32)->unique();
            $table->string('event_name', 100);
            $table->json('payload');
            $table->dateTime('occurred_on');
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['dispatched_at', 'occurred_on'], 'idx_pending');
            $table->index('event_name');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_event_outbox');
    }
};
