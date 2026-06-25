<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\DomainException;

final class SubscriptionNotFound extends DomainException
{
    public static function forUser(UserId $userId): self
    {
        return new self(sprintf('No subscription found for user %d.', $userId->value()));
    }
}
