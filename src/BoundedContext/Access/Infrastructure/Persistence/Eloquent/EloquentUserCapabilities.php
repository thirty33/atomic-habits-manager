<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent;

use Core\BoundedContext\Access\Application\Authorization\UserCapabilities;
use Illuminate\Support\Facades\DB;

/**
 * Resolves a user's capabilities: users -> role_user -> permission_role ->
 * permissions.code. Memoized per user for the request, because the sidebar
 * checks many capabilities on every page load. PKs are named (permission_id /
 * role_id / module_id) per this project's convention, so the JOIN columns use
 * those names.
 */
final class EloquentUserCapabilities implements UserCapabilities
{
    /** @var array<int, list<string>> */
    private array $cache = [];

    public function has(int $userId, string $code): bool
    {
        return in_array($code, $this->all($userId), true);
    }

    /** @return list<string> */
    public function all(int $userId): array
    {
        return $this->cache[$userId] ??= DB::table('permissions')
            ->join('permission_role', 'permission_role.permission_id', '=', 'permissions.permission_id')
            ->join('role_user', 'role_user.role_id', '=', 'permission_role.role_id')
            ->where('role_user.user_id', $userId)
            // Defensive: code is NOT NULL in the schema, but guard against rows
            // that ever slip through so the IN check below never matches null.
            ->whereNotNull('permissions.code')
            ->distinct()
            ->pluck('permissions.code')
            ->all();
    }
}
