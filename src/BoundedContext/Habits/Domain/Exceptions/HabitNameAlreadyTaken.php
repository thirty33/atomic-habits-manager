<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Domain\Exceptions;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitName;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

final class HabitNameAlreadyTaken extends DomainException implements ProvidesValidationErrors
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    private function __construct(string $message, private readonly array $errors)
    {
        parent::__construct($message);
    }

    public static function forName(HabitName $name): self
    {
        return new self(
            sprintf('Habit name "%s" is already taken.', $name->value()),
            ['name' => ['El campo Nombre ya esta en uso.']],
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return $this->errors;
    }
}
