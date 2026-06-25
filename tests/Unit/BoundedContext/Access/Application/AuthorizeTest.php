<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Access\Application;

use Core\BoundedContext\Access\Application\Authorization\Authorize;
use Core\BoundedContext\Access\Application\Authorization\UserCapabilities;
use Core\BoundedContext\Access\Domain\Permission\Capability;
use PHPUnit\Framework\TestCase;

class AuthorizeTest extends TestCase
{
    public function test_grants_when_user_has_the_capability_code(): void
    {
        $authorize = new Authorize($this->capabilitiesWith(7, ['habits.create', 'calendar.view']));

        $this->assertTrue($authorize(7, Capability::HabitsCreate));
        $this->assertTrue($authorize(7, Capability::CalendarView));
    }

    public function test_denies_when_user_lacks_the_capability_code(): void
    {
        $authorize = new Authorize($this->capabilitiesWith(7, ['habits.view']));

        $this->assertFalse($authorize(7, Capability::BackofficeAdmin));
    }

    public function test_denies_for_user_without_any_capability(): void
    {
        $authorize = new Authorize($this->capabilitiesWith(7, []));

        $this->assertFalse($authorize(99, Capability::HabitsView));
    }

    public function test_capability_module_code_is_segment_before_first_dot(): void
    {
        $this->assertSame('atomic_ia', Capability::AtomicIaUse->moduleCode());
        $this->assertSame('backoffice', Capability::BackofficeAdmin->moduleCode());
    }

    /**
     * @param  list<string>  $codes
     */
    private function capabilitiesWith(int $userId, array $codes): UserCapabilities
    {
        return new class($userId, $codes) implements UserCapabilities
        {
            /**
             * @param  list<string>  $codes
             */
            public function __construct(private int $userId, private array $codes) {}

            public function has(int $userId, string $code): bool
            {
                return in_array($code, $this->all($userId), true);
            }

            public function all(int $userId): array
            {
                return $userId === $this->userId ? $this->codes : [];
            }
        };
    }
}
