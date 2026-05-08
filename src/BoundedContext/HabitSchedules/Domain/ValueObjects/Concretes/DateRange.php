<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes;

final readonly class DateRange
{
    private function __construct(
        public string $startsFrom,
        public ?string $endsAt,
    ) {}

    public static function from(string $startsFrom, ?string $endsAt): self
    {
        self::assertYMD($startsFrom, 'startsFrom');

        if ($endsAt !== null) {
            self::assertYMD($endsAt, 'endsAt');

            if ($endsAt <= $startsFrom) {
                throw new \InvalidArgumentException(sprintf(
                    'endsAt (%s) must be greater than startsFrom (%s).',
                    $endsAt,
                    $startsFrom,
                ));
            }
        }

        return new self($startsFrom, $endsAt);
    }

    private static function assertYMD(string $value, string $field): void
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                '%s must match YYYY-MM-DD, got "%s".',
                $field,
                $value,
            ));
        }

        $parts = explode('-', $value);

        if (! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            throw new \InvalidArgumentException(sprintf(
                '%s ("%s") is not a valid date.',
                $field,
                $value,
            ));
        }
    }
}
