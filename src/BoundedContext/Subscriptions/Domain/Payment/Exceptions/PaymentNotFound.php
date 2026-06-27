<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions;

use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;
use Core\Shared\Domain\DomainException;

final class PaymentNotFound extends DomainException
{
    public static function withId(PaymentId $id): self
    {
        return new self(sprintf('Payment %d not found.', $id->value()));
    }
}
