<?php

declare(strict_types=1);

namespace App\Services;

use Core\BoundedContext\Identity\Application\Actions\ActivateUser;
use Core\BoundedContext\Identity\Application\Actions\DeactivateUser;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;

/**
 * Thin application service for the backoffice Users module. Keeps controllers
 * out of direct contact with the Identity use cases (Controller -> Service ->
 * use cases), matching the project's Controller->Service convention.
 */
final readonly class UserService
{
    public function __construct(
        private ActivateUser $activateUser,
        private DeactivateUser $deactivateUser,
    ) {}

    /**
     * Toggles a user's activation state. Returns the resulting user so the
     * caller can build a context-aware notification.
     */
    public function toggleActivation(int $userId, bool $activate): UserResponse
    {
        return $activate
            ? ($this->activateUser)($userId)
            : ($this->deactivateUser)($userId);
    }
}
