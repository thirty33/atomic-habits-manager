<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_schedules', function (Blueprint $table) {
            $table->id('habit_schedule_id');

            $table->unsignedBigInteger('habit_id');
            $table->foreign('habit_id')->references('habit_id')->on('habits')->cascadeOnDelete();
            $table->foreignId('previous_schedule_id')->nullable()->constrained('habit_schedules', 'habit_schedule_id')->nullOnDelete();
            $table->string('chain_cue', 500)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'every_n_days']);
            $table->json('days_of_week')->nullable();
            $table->smallInteger('interval_days')->nullable();
            $table->date('specific_date')->nullable();
            $table->date('starts_from');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_schedules');
    }
};