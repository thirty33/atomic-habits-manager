<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain;

use Core\BoundedContext\Identity\Domain\Events\UserClaimedAccount;
use Core\BoundedContext\Identity\Domain\Events\UserEmailWasVerified;
use Core\BoundedContext\Identity\Domain\Events\UserHasLoggedIn;
use Core\BoundedContext\Identity\Domain\Events\UserHasLoggedOut;
use Core\BoundedContext\Identity\Domain\Events\UserPasswordWasChanged;
use Core\BoundedContext\Identity\Domain\Events\UserPasswordWasReset;
use Core\BoundedContext\Identity\Domain\Events\UserWasActivated;
use Core\BoundedContext\Identity\Domain\Events\UserWasDeactivated;
use Core\BoundedContext\Identity\Domain\Events\UserWasRegistered;
use Core\BoundedContext\Identity\Domain\Exceptions\AccountAlreadyClaimed;
use Core\BoundedContext\Identity\Domain\Exceptions\InvalidCredentials;
use Core\BoundedContext\Identity\Domain\Exceptions\UserNotActive;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\HashedPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PersonName;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\RememberToken;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

final class User extends AggregateRoot
{
    private function __construct(
        private ?UserId $userId,
        private PersonName $name,
        private EmailAddress $email,
        private HashedPassword $password,
        private bool $isActive,
        private bool $isAdmin,
        private ?DateTimeImmutable $emailVerifiedAt,
        private ?DateTimeImmutable $claimedAt,
        private ?RememberToken $rememberToken,
        private ?DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {}

    public static function register(
        PersonName $name,
        EmailAddress $email,
        PlainPassword $plain,
        PasswordHasher $hasher,
    ): self {
        return new self(
            userId: null,
            name: $name,
            email: $email,
            password: $hasher->hash($plain),
            isActive: true,
            isAdmin: false,
            emailVerifiedAt: null,
            claimedAt: new DateTimeImmutable,
            rememberToken: null,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
        );
    }

    /**
     * Factory for a guest auto-user (D1): placeholder name, temporary unique
     * email, immediately active, and a non-usable random password (hashed so the
     * HashedPassword invariant holds, but never matched by login). The guest is
     * explicitly unclaimed (`claimedAt = null`); the real identity is filled
     * later via {@see self::claim()}.
     */
    public static function registerGuest(
        PersonName $placeholderName,
        EmailAddress $temporaryEmail,
        PasswordHasher $hasher,
    ): self {
        return new self(
            userId: null,
            name: $placeholderName,
            email: $temporaryEmail,
            password: $hasher->hash(PlainPassword::from(bin2hex(random_bytes(16)))),
            isActive: true,
            isAdmin: false,
            emailVerifiedAt: null,
            claimedAt: null,
            rememberToken: null,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
        );
    }

    /**
     * Fills real name, email and password over an existing (guest) user id,
     * completing the guest-claim flow (D1). Records {@see UserClaimedAccount} so
     * the repository can publish it. Guards against re-claiming an already
     * registered account, which would otherwise overwrite its identity.
     *
     * @throws AccountAlreadyClaimed
     */
    public function claim(
        PersonName $name,
        EmailAddress $email,
        PlainPassword $plain,
        PasswordHasher $hasher,
    ): void {
        if (! $this->isGuest()) {
            throw AccountAlreadyClaimed::create();
        }

        $this->name = $name;
        $this->email = $email;
        $this->password = $hasher->hash($plain);
        $this->claimedAt = new DateTimeImmutable;

        $this->record(UserClaimedAccount::now($this->userId, $email, $name));
    }

    public static function fromPrimitives(
        int $userId,
        string $name,
        string $email,
        string $hashedPassword,
        bool $isActive,
        bool $isAdmin,
        ?string $emailVerifiedAt,
        ?string $claimedAt,
        ?string $rememberToken,
        ?string $createdAt,
        ?string $updatedAt,
        ?string $deletedAt,
    ): self {
        return new self(
            userId: UserId::from($userId),
            name: PersonName::from($name),
            email: EmailAddress::from($email),
            password: HashedPassword::from($hashedPassword),
            isActive: $isActive,
            isAdmin: $isAdmin,
            emailVerifiedAt: $emailVerifiedAt !== null ? new DateTimeImmutable($emailVerifiedAt) : null,
            claimedAt: $claimedAt !== null ? new DateTimeImmutable($claimedAt) : null,
            rememberToken: $rememberToken !== null ? RememberToken::from($rememberToken) : null,
            createdAt: $createdAt !== null ? new DateTimeImmutable($createdAt) : null,
            updatedAt: $updatedAt !== null ? new DateTimeImmutable($updatedAt) : null,
            deletedAt: $deletedAt !== null ? new DateTimeImmutable($deletedAt) : null,
        );
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function name(): PersonName
    {
        return $this->name;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function rememberToken(): ?RememberToken
    {
        return $this->rememberToken;
    }

    public function isVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function claimedAt(): ?DateTimeImmutable
    {
        return $this->claimedAt;
    }

    /**
     * Whether this is still an unclaimed guest auto-user (D1). Derived from the
     * explicit `claimedAt` flag (null = guest), NOT from the email suffix: a real
     * account that happens to use a `@guest.local` address is not a guest.
     */
    public function isGuest(): bool
    {
        return $this->claimedAt === null;
    }

    public function isClaimed(): bool
    {
        return ! $this->isGuest();
    }

    public function isNew(): bool
    {
        return $this->userId === null;
    }

    public function hasId(): bool
    {
        return $this->userId !== null;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function assignId(UserId $id): void
    {
        if ($this->userId !== null) {
            throw new \DomainException('User already has an ID.');
        }

        $this->userId = $id;
    }

    /**
     * Records the "user registered" event AFTER the repository has assigned the
     * DB-generated id. This is a deliberate pattern, not a leak: with
     * auto-increment PKs the id does not exist at factory time, so the creation
     * event genuinely cannot be recorded inside register()/registerGuest(). The
     * repository calls this once, immediately after assignId(), then pulls and
     * publishes. (Subscription stages its transition events instead because they
     * can also fire before a first persist; here only creation is at stake.)
     */
    public function recordRegisteredAfterAssign(): void
    {
        $this->record(UserWasRegistered::now($this->userId, $this->email, $this->name));
    }

    public function logIn(PlainPassword $plain, PasswordHasher $hasher): void
    {
        if (! $this->isActive) {
            throw UserNotActive::withId($this->userId);
        }

        if (! $hasher->matches($plain, $this->password)) {
            throw InvalidCredentials::forEmail($this->email);
        }

        $this->record(UserHasLoggedIn::now($this->userId));
    }

    public function logOut(): void
    {
        $this->record(UserHasLoggedOut::now($this->userId));
    }

    public function verifyEmail(DateTimeImmutable $now): void
    {
        if ($this->emailVerifiedAt !== null) {
            return;
        }

        $this->emailVerifiedAt = $now;
        $this->record(UserEmailWasVerified::at($this->userId, $now));
    }

    public function resetPassword(PlainPassword $plain, PasswordHasher $hasher): void
    {
        $this->password = $hasher->hash($plain);
        $this->rememberToken = RememberToken::generate();
        $this->record(UserPasswordWasReset::now($this->userId));
    }

    public function changePassword(
        PlainPassword $currentPlain,
        PlainPassword $newPlain,
        PasswordHasher $hasher,
    ): void {
        if (! $hasher->matches($currentPlain, $this->password)) {
            throw InvalidCredentials::forPasswordChange($this->userId);
        }

        $this->password = $hasher->hash($newPlain);
        $this->record(UserPasswordWasChanged::now($this->userId));
    }

    public function activate(): void
    {
        if ($this->isActive) {
            return;
        }

        $this->isActive = true;
        $this->record(UserWasActivated::now($this->userId));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            return;
        }

        $this->isActive = false;
        $this->record(UserWasDeactivated::now($this->userId));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable;
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }
}
