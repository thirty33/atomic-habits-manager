<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\Actions;

use Core\BoundedContext\DailyReports\Application\ReadModels\AdherenceDaySnapshot;
use Core\BoundedContext\DailyReports\Application\ReadModels\StreakSnapshot;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use DateTimeImmutable;

/**
 * Current completion streak: consecutive days (ending today) where every
 * scheduled occurrence was completed. Days with nothing scheduled are neutral
 * (they neither extend nor break the streak).
 */
final readonly class CalculateStreak
{
    private const int WINDOW_DAYS = 60;

    private const int CELLS = 21;

    public function __construct(private CalculateAdherenceForRange $adherence) {}

    public function __invoke(UserId $userId, ?string $today = null): StreakSnapshot
    {
        $today ??= date('Y-m-d');
        $from = (new DateTimeImmutable($today))
            ->modify('-'.(self::WINDOW_DAYS - 1).' days')
            ->format('Y-m-d');

        $byDate = [];
        foreach (($this->adherence)($userId, $from, $today, $today) as $day) {
            $byDate[$day->date] = $day;
        }

        return new StreakSnapshot(
            count: $this->currentStreak($byDate, $today),
            cells: $this->cells($byDate, $today),
            fromDate: (new DateTimeImmutable($today))->modify('-'.(self::CELLS - 1).' days')->format('Y-m-d'),
            toDate: $today,
            isRecord: $this->isRecord($byDate, $today),
        );
    }

    /**
     * @param  array<string, AdherenceDaySnapshot>  $byDate
     */
    private function currentStreak(array $byDate, string $today): int
    {
        $count = 0;
        $cursor = new DateTimeImmutable($today);

        for ($i = 0; $i < self::WINDOW_DAYS; $i++) {
            $day = $byDate[$cursor->format('Y-m-d')] ?? null;

            if ($this->isNeutral($day)) {
                $cursor = $cursor->modify('-1 day');

                continue;
            }

            if ($this->isComplete($day)) {
                $count++;
                $cursor = $cursor->modify('-1 day');

                continue;
            }

            break;
        }

        return $count;
    }

    /**
     * @param  array<string, AdherenceDaySnapshot>  $byDate
     * @return list<int>
     */
    private function cells(array $byDate, string $today): array
    {
        $cells = [];
        $cursor = (new DateTimeImmutable($today))->modify('-'.(self::CELLS - 1).' days');

        for ($i = 0; $i < self::CELLS; $i++) {
            $cells[] = $this->isComplete($byDate[$cursor->format('Y-m-d')] ?? null) ? 1 : 0;
            $cursor = $cursor->modify('+1 day');
        }

        return $cells;
    }

    /**
     * The current streak is a personal record when it is at least as long as
     * the longest completed run within the window.
     *
     * @param  array<string, AdherenceDaySnapshot>  $byDate
     */
    private function isRecord(array $byDate, string $today): bool
    {
        $count = $this->currentStreak($byDate, $today);
        if ($count === 0) {
            return false;
        }

        $longest = 0;
        $run = 0;
        foreach ($byDate as $day) {
            if ($this->isComplete($day)) {
                $run++;
                $longest = max($longest, $run);
            } elseif (! $this->isNeutral($day)) {
                $run = 0;
            }
        }

        return $count >= $longest;
    }

    private function isComplete(?AdherenceDaySnapshot $day): bool
    {
        return $day !== null && $day->total > 0 && $day->percentage === 100;
    }

    private function isNeutral(?AdherenceDaySnapshot $day): bool
    {
        return $day === null || $day->total === 0;
    }
}
