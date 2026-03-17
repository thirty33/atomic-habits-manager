<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_entries', function (Blueprint $table) {
            $table->id('daily_report_entry_id');

            $table->unsignedBigInteger('daily_report_id');
            $table->foreign('daily_report_id')->references('daily_report_id')->on('daily_reports')->cascadeOnDelete();

            $table->unsignedBigInteger('habit_occurrence_id')->nullable();
            $table->foreign('habit_occurrence_id')->references('habit_occurrence_id')->on('habit_occurrences')->nullOnDelete();

            $table->unsignedBigInteger('habit_id')->nullable();
            $table->foreign('habit_id')->references('habit_id')->on('habits')->nullOnDelete();

            $table->string('custom_activity')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['daily_report_id', 'habit_occurrence_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_entries');
    }
};
