<?php

declare(strict_types=1);

namespace Tests\Feature\BoundedContext\Access;

use App\Models\User;
use Core\BoundedContext\Access\Application\Authorization\Authorize;
use Core\BoundedContext\Access\Application\Authorization\UserCapabilities;
use Core\BoundedContext\Access\Domain\Permission\Capability;
use Database\Seeders\AccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_provisions_modules_and_capability_permissions(): void
    {
        $this->seed(AccessSeeder::class);

        $this->assertDatabaseHas('modules', ['code' => 'habits']);
        $this->assertDatabaseHas('modules', ['code' => 'atomic_ia']);

        foreach (Capability::cases() as $capability) {
            $this->assertDatabaseHas('permissions', ['code' => $capability->value]);
        }

        $this->assertSame(
            count(Capability::cases()),
            \App\Models\Permission::query()->count(),
        );
    }

    public function test_seeder_creates_superadmin_role_with_backoffice_admin(): void
    {
        $this->seed(AccessSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => AccessSeeder::SUPERADMIN_ROLE]);

        $role = \App\Models\Role::query()->where('name', AccessSeeder::SUPERADMIN_ROLE)->firstOrFail();

        $this->assertContains(
            Capability::BackofficeAdmin->value,
            $role->permissions->pluck('code')->all(),
        );
    }

    public function test_seeder_assigns_role_to_existing_admin_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $regular = User::factory()->create(['is_admin' => false]);

        $this->seed(AccessSeeder::class);

        $this->assertTrue($admin->fresh()->roles()->exists());
        $this->assertFalse($regular->fresh()->roles()->exists());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AccessSeeder::class);
        $this->seed(AccessSeeder::class);

        $this->assertSame(count(Capability::cases()), \App\Models\Permission::query()->count());
        $this->assertSame(1, \App\Models\Role::query()->where('name', AccessSeeder::SUPERADMIN_ROLE)->count());
    }

    public function test_user_capabilities_resolves_codes_through_the_join(): void
    {
        $this->seed(AccessSeeder::class);

        $admin = User::factory()->create(['is_admin' => true]);
        $role = \App\Models\Role::query()->where('name', AccessSeeder::SUPERADMIN_ROLE)->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$role->role_id]);

        /** @var UserCapabilities $capabilities */
        $capabilities = app(UserCapabilities::class);

        $this->assertTrue($capabilities->has($admin->user_id, Capability::BackofficeAdmin->value));
        $this->assertContains(Capability::BackofficeAdmin->value, $capabilities->all($admin->user_id));
    }

    public function test_authorize_use_case_grants_superadmin_capability(): void
    {
        $this->seed(AccessSeeder::class);

        $admin = User::factory()->create(['is_admin' => true]);
        $role = \App\Models\Role::query()->where('name', AccessSeeder::SUPERADMIN_ROLE)->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$role->role_id]);

        /** @var Authorize $authorize */
        $authorize = app(Authorize::class);

        $this->assertTrue($authorize($admin->user_id, Capability::BackofficeAdmin));
        $this->assertFalse($authorize($admin->user_id + 999, Capability::BackofficeAdmin));
    }
}
