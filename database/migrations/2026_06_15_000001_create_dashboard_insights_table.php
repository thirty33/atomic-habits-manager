<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_insights', function (Blueprint $table): void {
            $table->bigIncrements('insight_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_insights');
    }
};
