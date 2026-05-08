<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use DateTimeImmutable;
use InvalidArgumentException;

final class EntryTime extends ValueObject
{
    private function __construct(
        private readonly string $startTime,
        private readonly string $endTime,
    ) {}

    public static function fromStrings(string $start, string $end): self
    {
        $normStart = self::normalize($start);
        $normEnd = self::normalize($end);

        $parsedStart = $normStart !== null ? DateTimeImmutable::createFromFormat('H:i', $normStart) : false;
        $parsedEnd = $normEnd !== null ? DateTimeImmutable::createFromFormat('H:i', $normEnd) : false;

        if ($parsedStart === false || $parsedEnd === false) {
            throw new InvalidArgumentException(
                sprintf('Invalid EntryTime [%s, %s]. Expected format H:i.', $start, $end)
            );
        }

        if ($parsedEnd <= $parsedStart) {
            throw new InvalidArgumentException(
                sprintf('EntryTime end (%s) must be strictly after start (%s).', $end, $start)
            );
        }

        return new self($normStart, $normEnd);
    }

    /**
     * Accept both H:i and H:i:s (MySQL TIME columns return the latter).
     */
    private static function normalize(string $time): ?string
    {
        if (preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $time, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    public function startTime(): string
    {
        return $this->startTime;
    }

    public function endTime(): string
    {
        return $this->endTime;
    }

    public function value(): array
    {
        return [
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }
}
