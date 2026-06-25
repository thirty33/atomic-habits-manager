<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions;

use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentStatus;
use Core\Shared\Domain\DomainException;

final class PaymentTransitionNotAllowed extends DomainException
{
    public static function from(PaymentStatus $current, string $action): self
    {
        return new self(sprintf('Cannot %s a payment in status "%s".', $action, $current->value()));
    }
}
