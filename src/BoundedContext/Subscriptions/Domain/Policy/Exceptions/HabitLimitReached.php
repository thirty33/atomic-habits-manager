<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Policy\Exceptions;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Raised when a plan would exceed its habit cap. Implements
 * ProvidesValidationErrors so the single render() in bootstrap/app.php maps it
 * to a 422 — the Habits BC can let it bubble out of CreateHabit unchanged.
 */
final class HabitLimitReached extends DomainException implements ProvidesValidationErrors
{
    public static function forTier(PlanTier $tier, int $maxHabits): self
    {
        $exception = new self(sprintf(
            'Plan "%s" allows at most %d habits.',
            $tier->value(),
            $maxHabits,
        ));
        $exception->maxHabits = $maxHabits;

        return $exception;
    }

    private int $maxHabits = 0;

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['name' => [sprintf(
            'Tu plan permite un máximo de %d hábitos. Mejora a unlimited para crear más.',
            $this->maxHabits,
        )]];
    }
}
