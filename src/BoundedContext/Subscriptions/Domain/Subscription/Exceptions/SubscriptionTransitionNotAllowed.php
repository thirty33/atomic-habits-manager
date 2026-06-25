<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions;

use Core\BoundedContext\Subscriptions\Domain\Subscription\ValueObjects\SubscriptionStatus;
use Core\Shared\Domain\DomainException;
use Core\Shared\Domain\ProvidesValidationErrors;

/**
 * An illegal subscription state transition. Implements ProvidesValidationErrors
 * so user-triggered illegal transitions (e.g. notifying a payment while one is
 * already pending) surface as a clean 422 via the single render() in
 * bootstrap/app.php, never a 500.
 */
final class SubscriptionTransitionNotAllowed extends DomainException implements ProvidesValidationErrors
{
    private function __construct(string $message, private readonly string $userMessage)
    {
        parent::__construct($message);
    }

    public static function from(SubscriptionStatus $current, string $action): self
    {
        return new self(
            sprintf('Cannot %s a subscription in status "%s".', $action, $current->value()),
            $current->isPaymentNotified()
                ? 'Ya tienes un pago pendiente de confirmación. Lo verificaremos a la brevedad.'
                : 'No se puede cambiar el estado de tu suscripción en este momento.',
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function validationErrors(): array
    {
        return ['plan_tier' => [$this->userMessage]];
    }
}
