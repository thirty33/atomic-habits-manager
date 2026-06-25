<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Identity\Domain;

use Core\BoundedContext\Identity\Domain\Events\UserClaimedAccount;
use Core\BoundedContext\Identity\Domain\Events\UserWasActivated;
use Core\BoundedContext\Identity\Domain\Events\UserWasDeactivated;
use Core\BoundedContext\Identity\Domain\Exceptions\AccountAlreadyClaimed;
use Core\BoundedContext\Identity\Domain\Services\PasswordHasher;
use Core\BoundedContext\Identity\Domain\User;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\HashedPassword;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PersonName;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\PlainPassword;
use PHPUnit\Framework\TestCase;

class UserAggregateTest extends TestCase
{
    private function hasher(): PasswordHasher
    {
        return new class implements PasswordHasher
        {
            public function hash(PlainPassword $plain): HashedPassword
            {
                return HashedPassword::from('$2y$10$'.str_pad(bin2hex(random_bytes(11)), 22, '0').'abcdefghijklmnopqrstuv');
            }

            public function matches(PlainPassword $plain, HashedPassword $hashed): bool
            {
                return false;
            }

            public function needsRehash(HashedPassword $hashed): bool
            {
                return false;
            }
        };
    }

    private function existingUser(bool $isActive = true): User
    {
        return User::fromPrimitives(
            userId: 7,
            name: 'Invitado',
            email: 'guest_abc@guest.local',
            hashedPassword: '$2y$10$abcdefghijklmnopqrstuvabcdefghijklmnopqrstuvabcdefghi',
            isActive: $isActive,
            isAdmin: false,
            emailVerifiedAt: null,
            claimedAt: null,
            rememberToken: null,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
        );
    }

    public function test_register_guest_is_active_with_placeholder_identity(): void
    {
        $user = User::registerGuest(
            placeholderName: PersonName::from('Invitado'),
            temporaryEmail: EmailAddress::from('guest_x@guest.local'),
            hasher: $this->hasher(),
        );

        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isNew());
        $this->assertSame('Invitado', $user->name()->value());
    }

    public function test_register_guest_is_a_guest_until_claimed(): void
    {
        $user = User::registerGuest(
            placeholderName: PersonName::from('Invitado'),
            temporaryEmail: EmailAddress::from('guest_x@guest.local'),
            hasher: $this->hasher(),
        );

        $this->assertTrue($user->isGuest());
        $this->assertNull($user->claimedAt());
        $this->assertFalse($user->isClaimed());
    }

    public function test_directly_registered_user_is_not_a_guest(): void
    {
        $user = User::register(
            name: PersonName::from('Real Name'),
            email: EmailAddress::from('real@example.com'),
            plain: PlainPassword::from('supersecret'),
            hasher: $this->hasher(),
        );

        $this->assertFalse($user->isGuest());
        $this->assertNotNull($user->claimedAt());
        $this->assertTrue($user->isClaimed());
    }

    public function test_a_real_user_with_a_guest_local_email_is_not_a_guest(): void
    {
        $user = User::fromPrimitives(
            userId: 9,
            name: 'Real Name',
            email: 'real_user@guest.local',
            hashedPassword: '$2y$10$abcdefghijklmnopqrstuvabcdefghijklmnopqrstuvabcdefghi',
            isActive: true,
            isAdmin: false,
            emailVerifiedAt: null,
            claimedAt: '2026-06-25 10:00:00',
            rememberToken: null,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
        );

        $this->assertFalse($user->isGuest());
        $this->assertTrue($user->isClaimed());
    }

    public function test_claim_fills_identity_and_records_event(): void
    {
        $user = $this->existingUser();

        $this->assertTrue($user->isGuest());

        $user->claim(
            name: PersonName::from('Real Name'),
            email: EmailAddress::from('real@example.com'),
            plain: PlainPassword::from('supersecret'),
            hasher: $this->hasher(),
        );

        $this->assertFalse($user->isGuest());
        $this->assertNotNull($user->claimedAt());
        $this->assertSame('Real Name', $user->name()->value());
        $this->assertSame('real@example.com', $user->email()->value());

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserClaimedAccount::class, $events[0]);
        $this->assertSame(7, $events[0]->userId->value());
    }

    public function test_claim_on_already_claimed_account_is_rejected_and_keeps_identity(): void
    {
        $user = User::fromPrimitives(
            userId: 7,
            name: 'Real Name',
            email: 'real@example.com',
            hashedPassword: '$2y$10$abcdefghijklmnopqrstuvabcdefghijklmnopqrstuvabcdefghi',
            isActive: true,
            isAdmin: false,
            emailVerifiedAt: null,
            claimedAt: '2026-06-25 10:00:00',
            rememberToken: null,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
        );

        try {
            $user->claim(
                name: PersonName::from('Hacker'),
                email: EmailAddress::from('hacker@example.com'),
                plain: PlainPassword::from('newsecret123'),
                hasher: $this->hasher(),
            );
            $this->fail('Expected AccountAlreadyClaimed.');
        } catch (AccountAlreadyClaimed) {
            // Identity must be untouched by the rejected re-claim.
        }

        $this->assertSame('Real Name', $user->name()->value());
        $this->assertSame('real@example.com', $user->email()->value());
        $this->assertSame([], $user->peekDomainEvents());
    }

    public function test_activate_records_event_when_state_changes(): void
    {
        $user = $this->existingUser(isActive: false);

        $user->activate();

        $this->assertTrue($user->isActive());
        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserWasActivated::class, $events[0]);
    }

    public function test_deactivate_records_event_when_state_changes(): void
    {
        $user = $this->existingUser(isActive: true);

        $user->deactivate();

        $this->assertFalse($user->isActive());
        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserWasDeactivated::class, $events[0]);
    }

    public function test_activate_is_idempotent_and_records_nothing_when_already_active(): void
    {
        $user = $this->existingUser(isActive: true);

        $user->activate();

        $this->assertSame([], $user->peekDomainEvents());
    }
}
