<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id('habit_id');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->enum('habit_nature', ['build', 'break']);
            $table->enum('desire_type', ['need', 'want', 'neutral']);
            $table->text('implementation_intention')->nullable();
            $table->string('location')->nullable();
            $table->string('cue')->nullable();
            $table->text('reframe')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};