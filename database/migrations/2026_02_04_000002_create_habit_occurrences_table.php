<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_occurrences', function (Blueprint $table) {
            $table->id('habit_occurrence_id');

            $table->unsignedBigInteger('habit_id');
            $table->foreign('habit_id')->references('habit_id')->on('habits')->cascadeOnDelete();

            $table->unsignedBigInteger('habit_schedule_id')->nullable();
            $table->foreign('habit_schedule_id')->references('habit_schedule_id')->on('habit_schedules')->nullOnDelete();
            $table->date('occurrence_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'completed', 'partial', 'not_completed', 'skipped'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['habit_id', 'occurrence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_occurrences');
    }
};