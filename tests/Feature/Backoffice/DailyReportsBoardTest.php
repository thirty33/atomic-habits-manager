<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Enums\Mood;
use App\Enums\ReportEntryStatus;
use App\Models\DailyReport;
use App\Models\DailyReportEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportsBoardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_board_json_requires_authentication(): void
    {
        $this->getJson(route('backoffice.daily-reports.board-json'))->assertUnauthorized();
    }

    public function test_board_json_returns_only_the_users_reports_with_meta_and_moods(): void
    {
        DailyReport::factory()->for($this->user)->create(['report_date' => '2026-05-10']);
        DailyReport::factory()->for($this->user)->create(['report_date' => '2026-05-11']);
        DailyReport::factory()->create(['report_date' => '2026-05-12']); // other user

        $response = $this->actingAs($this->user)
            ->getJson(route('backoffice.daily-reports.board-json'))
            ->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonCount(count(Mood::cases()), 'moods');
        $response->assertJsonStructure([
            'data' => [['daily_report_id', 'report_date', 'mood', 'mood_label', 'progress_percent', 'is_complete', 'notes', 'edit_url']],
            'meta' => ['current_page', 'last_page', 'total', 'per_page'],
            'moods' => [['value', 'label']],
        ]);
    }

    public function test_board_json_filters_by_mood(): void
    {
        DailyReport::factory()->for($this->user)->create(['report_date' => '2026-05-10', 'mood' => Mood::Great->value]);
        DailyReport::factory()->for($this->user)->create(['report_date' => '2026-05-11', 'mood' => Mood::Bad->value]);

        $this->actingAs($this->user)
            ->getJson(route('backoffice.daily-reports.board-json', ['mood' => Mood::Great->value]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mood', Mood::Great->value);
    }

    public function test_progress_percent_reflects_reported_entries(): void
    {
        $report = DailyReport::factory()->for($this->user)->create(['report_date' => '2026-05-10']);
        DailyReportEntry::factory()->create(['daily_report_id' => $report->getKey(), 'status' => ReportEntryStatus::Completed->value]);
        DailyReportEntry::factory()->create(['daily_report_id' => $report->getKey(), 'status' => ReportEntryStatus::Pending->value]);

        $this->actingAs($this->user)
            ->getJson(route('backoffice.daily-reports.board-json'))
            ->assertOk()
            ->assertJsonPath('data.0.progress_percent', 50)
            ->assertJsonPath('data.0.is_complete', false);
    }

    public function test_today_finds_or_creates_todays_report_and_redirects_to_edit(): void
    {
        $this->actingAs($this->user)
            ->get(route('backoffice.daily-reports.today'))
            ->assertRedirect();

        $this->assertDatabaseHas('daily_reports', [
            'user_id' => $this->user->user_id,
            'report_date' => now()->toDateString(),
        ]);
    }
}
