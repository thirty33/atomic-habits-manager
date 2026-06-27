<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Payment\Exceptions;

use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * Raised when the admin tries to confirm/reject a payment for a user who has no
 * payment awaiting reconciliation. Surfaced as a 422 by the single render() in
 * bootstrap/app.php.
 */
final class NoNotifiedPaymentForUser extends DomainException implements ProvidesValidationErrors
{
    public static function withId(int $userId): self
    {
        return new self(sprintf('User %d has no payment awaiting confirmation.', $userId));
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['payment' => [__('Este usuario no tiene un pago pendiente de confirmación.')]];
    }
}
