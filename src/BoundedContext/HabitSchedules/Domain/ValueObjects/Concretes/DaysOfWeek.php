<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes;

final class DaysOfWeek
{
    /** @var list<int> */
    private array $days;

    /**
     * @param  list<int>  $days
     */
    private function __construct(array $days)
    {
        if ($days === []) {
            throw new \InvalidArgumentException('DaysOfWeek must contain at least one day.');
        }

        $unique = [];

        foreach ($days as $d) {
            if (! is_int($d) || $d < 0 || $d > 6) {
                throw new \InvalidArgumentException(sprintf(
                    'DaysOfWeek elements must be int in [0, 6], got %s.',
                    is_int($d) ? (string) $d : gettype($d)
                ));
            }

            $unique[$d] = true;
        }

        $sorted = array_keys($unique);
        sort($sorted);

        $this->days = array_values($sorted);
    }

    /**
     * @param  list<int>  $days
     */
    public static function from(array $days): self
    {
        return new self($days);
    }

    /**
     * @return list<int>
     */
    public function value(): array
    {
        return $this->days;
    }

    /**
     * @return list<int>
     */
    public function toArray(): array
    {
        return $this->days;
    }
}
