<?php

namespace Tests\Feature\Backoffice;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveReportEntriesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DailyReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->report = DailyReport::factory()->create(['user_id' => $this->user->user_id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function entry(array $overrides = []): array
    {
        return array_merge([
            'custom_activity' => 'Dormir',
            'start_time' => '23:40',
            'end_time' => '07:00',
            'status' => 'completed',
        ], $overrides);
    }

    public function test_accepts_cross_midnight_entry(): void
    {
        $response = $this->actingAs($this->user)->putJson(
            route('backoffice.daily-reports.save-entries', $this->report->daily_report_id),
            ['entries' => [$this->entry()]]
        );

        $response->assertOk();
        $this->assertDatabaseHas('daily_report_entries', [
            'daily_report_id' => $this->report->daily_report_id,
            'start_time' => '23:40:00',
            'end_time' => '07:00:00',
        ]);
    }

    public function test_rejects_equal_start_and_end_entry(): void
    {
        $response = $this->actingAs($this->user)->putJson(
            route('backoffice.daily-reports.save-entries', $this->report->daily_report_id),
            ['entries' => [$this->entry(['start_time' => '08:00', 'end_time' => '08:00'])]]
        );

        $response->assertJsonValidationErrors('entries.0.end_time');
    }
}
