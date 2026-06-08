<?php

namespace Tests\Feature\BoundedContext\HabitOccurrences\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HabitOccurrenceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_habit_occurrences_has_end_date_column(): void
    {
        $this->assertTrue(Schema::hasColumn('habit_occurrences', 'end_date'));
    }

    public function test_habit_schedules_keeps_its_schema_without_cross_midnight_columns(): void
    {
        $this->assertFalse(Schema::hasColumn('habit_schedules', 'end_date'));
        $this->assertFalse(Schema::hasColumn('habit_schedules', 'crosses_midnight'));
    }
}
