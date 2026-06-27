<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Subscription\Exceptions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\DomainException;

/**
 * Raised when a SECOND current subscription is created for a user that already
 * has one. Expresses the "one current subscription per user" invariant in the
 * domain (the DB unique index on user_id is the persistence-level backstop).
 *
 * The idempotent ensure use case ({@see \Core\BoundedContext\Subscriptions\Application\Subscription\Actions\StartFreeSubscription})
 * never reaches this — it returns the existing subscription instead. This guards
 * any code path that tries to blindly create a duplicate.
 */
final class SubscriptionAlreadyExists extends DomainException
{
    public static function forUser(UserId $userId): self
    {
        return new self(sprintf('User %d already has a current subscription.', $userId->value()));
    }
}
