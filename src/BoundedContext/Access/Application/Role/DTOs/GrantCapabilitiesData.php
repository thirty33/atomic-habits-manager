<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Role\DTOs;

final readonly class GrantCapabilitiesData
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function __construct(
        public int $roleId,
        public array $permissionIds,
    ) {}
}
