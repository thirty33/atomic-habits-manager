<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Responses;

use Core\BoundedContext\Identity\Domain\User;

final readonly class UserResponse
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
        public bool $isActive,
        public bool $isAdmin,
        public bool $isVerified,
        public ?string $createdAt,
    ) {}

    public static function fromUser(User $user): self
    {
        $id = $user->userId();

        if ($id === null) {
            throw new \LogicException('Cannot build UserResponse from a User without id.');
        }

        return new self(
            userId: $id->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            isActive: $user->isActive(),
            isAdmin: $user->isAdmin(),
            isVerified: $user->isVerified(),
            createdAt: $user->createdAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
