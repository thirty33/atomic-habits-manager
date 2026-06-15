<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Infrastructure\Persistence\Eloquent;

use App\Models\DailyReport as DailyReportModel;
use App\Models\DailyReportEntry as DailyReportEntryModel;
use Carbon\Carbon;
use Core\BoundedContext\DailyReports\Application\DailyReportReader;
use Core\BoundedContext\DailyReports\Application\ReadModels\ReflectionSnapshot;
use Core\BoundedContext\DailyReports\Domain\Criteria\DailyReportsCriteria;
use Core\BoundedContext\DailyReports\Domain\Criteria\DailyReportsPage;
use Core\BoundedContext\DailyReports\Domain\DailyReport;
use Core\BoundedContext\DailyReports\Domain\DailyReportEntries;
use Core\BoundedContext\DailyReports\Domain\DailyReportEntry;
use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;
use Core\BoundedContext\DailyReports\Domain\DailyReports;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\CustomActivity;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportEntryId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryNotes;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryStatus;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\EntryTime;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\Mood;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportNotes;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Support\Facades\DB;

final readonly class EloquentDailyReportRepository implements DailyReportReader, DailyReportRepository
{
    public function __construct(
        private DailyReportModel $reportModel,
        private DailyReportEntryModel $entryModel,
        private EloquentDailyReportsCriteriaTranslator $criteriaTranslator,
    ) {}

    /**
     * @return array<int, string>
     */
    public function entryStatusesByOccurrence(UserId $userId, string $from, string $to): array
    {
        $rows = $this->entryModel->newQuery()
            ->toBase()
            ->join('daily_reports', 'daily_report_entries.daily_report_id', '=', 'daily_reports.daily_report_id')
            ->where('daily_reports.user_id', $userId->value())
            ->whereBetween('daily_reports.report_date', [$from, $to])
            ->whereNotNull('daily_report_entries.habit_occurrence_id')
            ->get([
                'daily_report_entries.habit_occurrence_id as habit_occurrence_id',
                'daily_report_entries.status as status',
            ]);

        $statuses = [];
        foreach ($rows as $row) {
            $statuses[(int) $row->habit_occurrence_id] = (string) $row->status;
        }

        return $statuses;
    }

    /**
     * @return list<string>
     */
    public function reportedDates(UserId $userId, string $from, string $to): array
    {
        return $this->reportModel->newQuery()
            ->toBase()
            ->where('user_id', $userId->value())
            ->whereBetween('report_date', [$from, $to])
            ->orderBy('report_date')
            ->pluck('report_date')
            ->map(fn ($date): string => substr((string) $date, 0, 10))
            ->unique()
            ->values()
            ->all();
    }

    public function latestReflection(UserId $userId): ?ReflectionSnapshot
    {
        $row = $this->reportModel->newQuery()
            ->where('user_id', $userId->value())
            ->orderByDesc('report_date')
            ->orderByDesc('daily_report_id')
            ->first();

        if ($row === null) {
            return null;
        }

        $attrs = $row->getAttributes();

        return new ReflectionSnapshot(
            dailyReportId: (int) $attrs['daily_report_id'],
            reportDate: substr((string) $attrs['report_date'], 0, 10),
            mood: isset($attrs['mood']) ? (string) $attrs['mood'] : null,
            notes: isset($attrs['notes']) ? (string) $attrs['notes'] : null,
            createdAt: isset($attrs['created_at']) ? (string) $attrs['created_at'] : '',
        );
    }

    public function save(DailyReport $report): void
    {
        DB::transaction(function () use ($report): void {
            $this->persistReport($report);

            foreach ($report->entries() as $entry) {
                $this->persistEntry($entry);
            }

            foreach ($report->pullRemovedEntries() as $removed) {
                $this->deleteEntry($removed);
            }
        });
    }

    public function findWithEntries(DailyReportId $id): ?DailyReport
    {
        $row = $this->reportModel->newQuery()->find($id->value());

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findForUserWithEntries(DailyReportId $id, UserId $userId): ?DailyReport
    {
        $row = $this->reportModel->newQuery()
            ->where('daily_report_id', $id->value())
            ->where('user_id', $userId->value())
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findByUserAndDate(UserId $userId, ReportDate $date): ?DailyReport
    {
        $row = $this->reportModel->newQuery()
            ->where('user_id', $userId->value())
            ->where('report_date', $date->value())
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function delete(DailyReport $report): void
    {
        $reportId = $report->id();

        if ($reportId === null) {
            throw new \LogicException('Cannot delete a DailyReport without id.');
        }

        $this->reportModel->newQuery()
            ->where('daily_report_id', $reportId->value())
            ->delete();
    }

    public function matching(DailyReportsCriteria $criteria): DailyReportsPage
    {
        $query = $this->criteriaTranslator->translate(
            $this->reportModel->newQuery(),
            $criteria,
        );

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page,
        );

        $reports = collect($paginator->items())
            ->map(fn (DailyReportModel $row) => $this->toDomain($row))
            ->all();

        return new DailyReportsPage(
            items: new DailyReports($reports),
            total: (int) $paginator->total(),
            page: (int) $paginator->currentPage(),
            perPage: (int) $paginator->perPage(),
        );
    }

    private function persistReport(DailyReport $report): void
    {
        if ($report->isPersisted()) {
            $this->updateReport($report);

            return;
        }

        $row = $this->reportModel->newInstance();
        $row->fill($this->reportAttributes($report));
        $row->save();

        $reportId = DailyReportId::from((int) $row->getKey());
        $report->assignId($reportId);
    }

    private function updateReport(DailyReport $report): void
    {
        $reportId = $report->id();

        if ($reportId === null) {
            throw new \LogicException('Report reports isPersisted() but has no id.');
        }

        $row = $this->reportModel->newQuery()->find($reportId->value());

        if ($row === null) {
            throw new \RuntimeException(sprintf(
                'Cannot update DailyReport %d: row missing in DB.',
                $reportId->value()
            ));
        }

        $row->fill($this->reportAttributes($report));
        $row->save();
    }

    private function persistEntry(DailyReportEntry $entry): void
    {
        if ($entry->isPersisted()) {
            $this->updateEntry($entry);

            return;
        }

        $reportId = $entry->dailyReportId();

        if ($reportId === null) {
            throw new \LogicException('Cannot persist entry without dailyReportId.');
        }

        $row = $this->entryModel->newInstance();
        $row->fill($this->entryAttributes($entry, $reportId));
        $row->save();

        $entryId = DailyReportEntryId::from((int) $row->getKey());
        $entry->assignId($entryId);
    }

    private function updateEntry(DailyReportEntry $entry): void
    {
        $entryId = $entry->id();

        if ($entryId === null) {
            throw new \LogicException('Entry reports isPersisted() but has no id.');
        }

        $row = $this->entryModel->newQuery()->find($entryId->value());

        if ($row === null) {
            throw new \RuntimeException(sprintf(
                'Cannot update DailyReportEntry %d: row missing in DB.',
                $entryId->value()
            ));
        }

        $reportId = $entry->dailyReportId();
        $row->fill($this->entryAttributes($entry, $reportId));
        $row->save();
    }

    private function deleteEntry(DailyReportEntry $entry): void
    {
        $entryId = $entry->id();

        if ($entryId === null) {
            return;
        }

        $this->entryModel->newQuery()
            ->where('daily_report_entry_id', $entryId->value())
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function reportAttributes(DailyReport $report): array
    {
        return [
            'user_id' => $report->userId()->value(),
            'report_date' => $report->reportDate()->value(),
            'notes' => $report->notes()?->value(),
            'mood' => $report->mood()?->value(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entryAttributes(DailyReportEntry $entry, DailyReportId $reportId): array
    {
        return [
            'daily_report_id' => $reportId->value(),
            'habit_occurrence_id' => $entry->habitOccurrenceId()?->value(),
            'habit_id' => $entry->habitId()?->value(),
            'custom_activity' => $entry->customActivity()?->value(),
            'start_time' => $entry->time()->startTime(),
            'end_time' => $entry->time()->endTime(),
            'status' => $entry->status()->value(),
            'completed_at' => $entry->completedAt()?->format('Y-m-d H:i:s'),
            'notes' => $entry->notes()?->value(),
        ];
    }

    private function toDomain(DailyReportModel $row): DailyReport
    {
        $attrs = $row->getAttributes();

        $entryRows = $row->entries()->get();

        $entries = [];
        foreach ($entryRows as $entryRow) {
            $entryAttrs = $entryRow->getAttributes();
            $entries[] = $this->entryToDomain($entryAttrs);
        }

        return DailyReport::reconstitute(
            id: DailyReportId::from((int) $attrs['daily_report_id']),
            userId: UserId::from((int) $attrs['user_id']),
            reportDate: ReportDate::fromString($attrs['report_date']),
            notes: $this->nullableValue($attrs, 'notes', ReportNotes::class),
            mood: $this->nullableValue($attrs, 'mood', Mood::class),
            entries: new DailyReportEntries($entries),
            createdAt: $this->toCarbon($attrs['created_at'])->toDateTimeImmutable(),
            updatedAt: $this->nullableCarbon($attrs, 'updated_at')?->toDateTimeImmutable(),
        );
    }

    private function entryToDomain(array $attrs): DailyReportEntry
    {
        return DailyReportEntry::reconstitute(
            id: DailyReportEntryId::from((int) $attrs['daily_report_entry_id']),
            dailyReportId: DailyReportId::from((int) $attrs['daily_report_id']),
            habitOccurrenceId: $this->nullableId($attrs, 'habit_occurrence_id', HabitOccurrenceId::class),
            habitId: $this->nullableId($attrs, 'habit_id', HabitId::class),
            customActivity: $this->nullableValue($attrs, 'custom_activity', CustomActivity::class),
            time: EntryTime::fromStrings(
                (string) $attrs['start_time'],
                (string) $attrs['end_time']
            ),
            status: EntryStatus::from($attrs['status']),
            completedAt: $this->nullableCarbon($attrs, 'completed_at')?->toDateTimeImmutable(),
            notes: $this->nullableValue($attrs, 'notes', EntryNotes::class),
            createdAt: $this->toCarbon($attrs['created_at'])->toDateTimeImmutable(),
            updatedAt: $this->nullableCarbon($attrs, 'updated_at')?->toDateTimeImmutable(),
        );
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @param  class-string  $class
     */
    private function nullableValue(array $attrs, string $key, string $class): ?object
    {
        if (! array_key_exists($key, $attrs) || $attrs[$key] === null) {
            return null;
        }

        return $class::from($attrs[$key]);
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @param  class-string  $class
     */
    private function nullableId(array $attrs, string $key, string $class): ?object
    {
        if (! array_key_exists($key, $attrs) || $attrs[$key] === null) {
            return null;
        }

        return $class::from((int) $attrs[$key]);
    }

    private function toCarbon(mixed $value): Carbon
    {
        return Carbon::parse($value);
    }

    private function nullableCarbon(array $attrs, string $key): ?Carbon
    {
        if (! array_key_exists($key, $attrs) || $attrs[$key] === null) {
            return null;
        }

        return Carbon::parse($attrs[$key]);
    }
}
