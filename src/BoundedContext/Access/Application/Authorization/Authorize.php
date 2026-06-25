<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Authorization;

use Core\BoundedContext\Access\Domain\Permission\Capability;

/**
 * Application use case that returns the authorization rule from the domain: is
 * the user allowed to perform a capability? The rule lives in data
 * (permission_role); this use case evaluates it. Read-only (no transaction).
 * Inject THIS wherever a rule is needed (policies, middleware, sidebar) — never
 * the framework Gate / $user->can().
 *
 * Callers pass a Capability enum, never a raw string: the code literals have a
 * single home (the Capability enum).
 */
final readonly class Authorize
{
    public function __construct(private UserCapabilities $capabilities) {}

    public function __invoke(int $userId, Capability $capability): bool
    {
        return $this->capabilities->has($userId, $capability->value);
    }
}
