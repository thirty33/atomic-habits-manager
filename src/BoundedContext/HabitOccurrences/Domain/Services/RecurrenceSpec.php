<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain\Services;

use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceTime;
use InvalidArgumentException;

final readonly class RecurrenceSpec
{
    public const TYPE_DAILY = 'daily';

    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_EVERY_N_DAYS = 'every_n_days';

    public const TYPE_NONE = 'none';

    private function __construct(
        public string $type,
        public OccurrenceTime $timeWindow,
        public ?OccurrenceDate $startsFrom,
        public ?OccurrenceDate $endsAt,
        public ?array $daysOfWeek,
        public ?int $intervalDays,
        public ?OccurrenceDate $specificDate,
    ) {
        $this->assertCoherent();
    }

    public static function daily(
        OccurrenceTime $timeWindow,
        ?OccurrenceDate $startsFrom = null,
        ?OccurrenceDate $endsAt = null,
    ): self {
        return new self(self::TYPE_DAILY, $timeWindow, $startsFrom, $endsAt, null, null, null);
    }

    /**
     * @param  list<int>  $daysOfWeek  0..6 (0=domingo).
     */
    public static function weekly(
        OccurrenceTime $timeWindow,
        array $daysOfWeek,
        ?OccurrenceDate $startsFrom = null,
        ?OccurrenceDate $endsAt = null,
    ): self {
        return new self(self::TYPE_WEEKLY, $timeWindow, $startsFrom, $endsAt, $daysOfWeek, null, null);
    }

    public static function everyNDays(
        OccurrenceTime $timeWindow,
        int $intervalDays,
        OccurrenceDate $anchor,
        ?OccurrenceDate $endsAt = null,
    ): self {
        return new self(self::TYPE_EVERY_N_DAYS, $timeWindow, $anchor, $endsAt, null, $intervalDays, null);
    }

    public static function oneOff(
        OccurrenceTime $timeWindow,
        OccurrenceDate $specificDate,
    ): self {
        return new self(self::TYPE_NONE, $timeWindow, null, null, null, null, $specificDate);
    }

    private function assertCoherent(): void
    {
        switch ($this->type) {
            case self::TYPE_DAILY:
                break;
            case self::TYPE_WEEKLY:
                if ($this->daysOfWeek === null || $this->daysOfWeek === []) {
                    throw new InvalidArgumentException('RecurrenceSpec weekly requires non-empty daysOfWeek');
                }
                foreach ($this->daysOfWeek as $d) {
                    if ($d < 0 || $d > 6) {
                        throw new InvalidArgumentException('daysOfWeek must contain values in 0..6');
                    }
                }
                break;
            case self::TYPE_EVERY_N_DAYS:
                if ($this->intervalDays === null || $this->intervalDays < 1) {
                    throw new InvalidArgumentException('RecurrenceSpec every_n_days requires intervalDays >= 1');
                }
                if ($this->startsFrom === null) {
                    throw new InvalidArgumentException('RecurrenceSpec every_n_days requires anchor (startsFrom)');
                }
                break;
            case self::TYPE_NONE:
                if ($this->specificDate === null) {
                    throw new InvalidArgumentException('RecurrenceSpec none requires specificDate');
                }
                break;
            default:
                throw new InvalidArgumentException("Unknown recurrence type: {$this->type}");
        }
    }
}
