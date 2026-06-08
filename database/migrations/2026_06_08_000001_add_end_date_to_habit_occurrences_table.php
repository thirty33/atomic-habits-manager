<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habit_occurrences', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('occurrence_date');
        });
    }

    public function down(): void
    {
        Schema::table('habit_occurrences', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }
};
