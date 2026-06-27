<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions;

use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

final class InvalidBinanceEmail extends DomainException implements ProvidesValidationErrors
{
    public static function for(string $value): self
    {
        return new self(sprintf('"%s" is not a valid Binance email.', $value));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['payer_binance_email' => ['El correo de Binance no es válido.']];
    }
}
