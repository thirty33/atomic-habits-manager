<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain\ValueObjects;

use Core\Shared\Domain\ValueObjects\ValueObject;
use DateTimeImmutable;
use InvalidArgumentException;

final class OccurrenceTime extends ValueObject
{
    private string $startTime;

    private string $endTime;

    public function __construct(string $startTime, string $endTime)
    {
        $this->validateFormat($startTime, 'startTime');
        $this->validateFormat($endTime, 'endTime');

        if ($this->normalize($endTime) === $this->normalize($startTime)) {
            throw new InvalidArgumentException('OccurrenceTime: end_time must differ from start_time');
        }

        $this->startTime = $this->normalize($startTime);
        $this->endTime = $this->normalize($endTime);
    }

    /**
     * A window crosses midnight when its end clock-time falls before its start
     * clock-time (e.g. 23:00 -> 07:00). The equal case is rejected on construction.
     */
    public function crossesMidnight(): bool
    {
        return $this->endTime < $this->startTime;
    }

    public function startTime(): string
    {
        return $this->startTime;
    }

    public function endTime(): string
    {
        return $this->endTime;
    }

    public function durationMinutes(): int
    {
        $start = DateTimeImmutable::createFromFormat('H:i', $this->startTime);
        $end = DateTimeImmutable::createFromFormat('H:i', $this->endTime);

        $minutes = (int) (($end->getTimestamp() - $start->getTimestamp()) / 60);

        return $minutes > 0 ? $minutes : $minutes + 1440;
    }

    /**
     * @return array{start: string, end: string}
     */
    public function value(): array
    {
        return ['start' => $this->startTime, 'end' => $this->endTime];
    }

    public function equals(ValueObject $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        return $this->startTime === $other->startTime && $this->endTime === $other->endTime;
    }

    private function validateFormat(string $time, string $field): void
    {
        if (DateTimeImmutable::createFromFormat('H:i', $time) === false
            && DateTimeImmutable::createFromFormat('H:i:s', $time) === false) {
            throw new InvalidArgumentException("OccurrenceTime: invalid {$field} format. Expected H:i or H:i:s");
        }
    }

    private function normalize(string $time): string
    {
        return substr($time, 0, 5);
    }
}
