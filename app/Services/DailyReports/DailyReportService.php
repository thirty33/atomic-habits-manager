<?php

namespace App\Services\DailyReports;

use App\Actions\DailyReports\CreateDailyReportAction;
use App\Actions\DailyReports\DeleteDailyReportAction;
use App\Actions\DailyReports\SaveReportEntriesAction;
use App\Actions\DailyReports\UpdateDailyReportAction;
use App\Models\DailyReport;
use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Repositories\DailyReportRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DailyReportService
{
    public function __construct(
        private DailyReportRepository $repository,
    ) {}

    public function queryForUser(int $userId): Builder
    {
        return $this->repository->queryForUser($userId);
    }

    public function findWithEntries(int $reportId): ?DailyReport
    {
        return $this->repository->findWithEntries($reportId);
    }

    public function findOrCreateForDate(int $userId, string $date): DailyReport
    {
        $report = $this->repository->findForUserAndDate($userId, $date);

        if ($report) {
            return $report;
        }

        return CreateDailyReportAction::execute([
            'user_id' => $userId,
            'report_date' => $date,
        ]);
    }

    public function update(int $id, array $data): void
    {
        UpdateDailyReportAction::execute($id, $data);
    }

    public function saveEntries(int $reportId, array $entries): void
    {
        SaveReportEntriesAction::execute([
            'daily_report_id' => $reportId,
            'entries' => $entries,
        ]);
    }

    public function delete(int $id): void
    {
        DeleteDailyReportAction::execute($id);
    }

    /**
     * @return Collection<int, HabitOccurrence>
     */
    public function getOccurrencesForDate(int $userId, string $date): Collection
    {
        return $this->repository->getOccurrencesForDate($userId, $date);
    }

    /**
     * @return Collection<int, Habit>
     */
    public function getActiveHabitsForUser(int $userId): Collection
    {
        return $this->repository->getActiveHabitsForUser($userId);
    }
}
