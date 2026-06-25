<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Authorization;

/**
 * Read-side port: resolves the capability codes a user has, derived from the
 * user's role(s) via the permission_role pivot. Generic — it knows no concrete
 * code; callers pass the code.
 */
interface UserCapabilities
{
    public function has(int $userId, string $code): bool;

    /** @return list<string> all capability codes granted to the user */
    public function all(int $userId): array;
}
