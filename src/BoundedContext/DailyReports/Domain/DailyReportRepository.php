<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain;

use Core\BoundedContext\DailyReports\Application\Criteria\DailyReportsCriteria;
use Core\BoundedContext\DailyReports\Domain\Criteria\DailyReportsPage;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

/**
 * Persistence port for the DailyReport aggregate.
 *
 * Domain rules:
 *  - Zero imports from `Illuminate\…` or `App\…`.
 *  - Save persists the full aggregate (report + entries diff) atomically.
 */
interface DailyReportRepository
{
    /**
     * Persist the aggregate. New report → INSERT; existing → UPDATE.
     * Entries are reconciled: new ones inserted, existing ones updated,
     * removed ones (from pullRemovedEntries) deleted. All in one transaction.
     *
     * Assigns DailyReportId on first save and DailyReportEntryId on each
     * new entry inserted.
     */
    public function save(DailyReport $report): void;

    /**
     * Find by id. Eagerly loads entries.
     */
    public function findWithEntries(DailyReportId $id): ?DailyReport;

    /**
     * Same as findWithEntries but checks ownership: returns null if the
     * report does not belong to the given user.
     */
    public function findForUserWithEntries(DailyReportId $id, UserId $userId): ?DailyReport;

    /**
     * Find a user's report for a specific date. Returns null if no report
     * exists for that (user, date) pair. Eagerly loads entries.
     */
    public function findByUserAndDate(UserId $userId, ReportDate $date): ?DailyReport;

    /**
     * Delete (cascading entries via FK).
     */
    public function delete(DailyReport $report): void;

    /**
     * List user's reports, paginated and filtered.
     */
    public function matching(DailyReportsCriteria $criteria): DailyReportsPage;
}
