<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Domain\Exceptions;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\Shared\Domain\DomainException;

/**
 * Excepción de dominio: se intentó operar sobre un Habit que no existe
 * (o que no pertenece al usuario solicitante).
 *
 * Application la lanza; Infrastructure (bootstrap/app.php) la traduce a
 * un 404 HTTP. El dominio nunca conoce el código HTTP.
 */
final class HabitNotFound extends DomainException
{
    public static function withId(HabitId $id): self
    {
        return new self(sprintf('Habit with id %d not found.', $id->value()));
    }
}
