<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Access\Application\Authorization\UserCapabilities;
use Core\BoundedContext\Access\Application\Module\ModuleReader;
use Core\BoundedContext\Access\Application\Permission\PermissionReader;
use Core\BoundedContext\Access\Application\Role\RoleReader;
use Core\BoundedContext\Access\Domain\Module\Events\ModuleWasCreated;
use Core\BoundedContext\Access\Domain\Module\ModuleRepository;
use Core\BoundedContext\Access\Domain\Permission\Events\PermissionWasCreated;
use Core\BoundedContext\Access\Domain\Permission\PermissionRepository;
use Core\BoundedContext\Access\Domain\Role\Events\RoleWasCreated;
use Core\BoundedContext\Access\Domain\Role\RoleRepository;
use Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent\EloquentModuleRepository;
use Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent\EloquentPermissionRepository;
use Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent\EloquentRoleRepository;
use Core\BoundedContext\Access\Infrastructure\Persistence\Eloquent\EloquentUserCapabilities;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the Access bounded context contracts (Roles, Permissions, Modules) to
 * their Eloquent implementations. Use cases receive these via constructor
 * auto-resolution — no app()/resolve() in the domain/application.
 *
 * TransactionManager and DomainEventBus (Core\Shared kernel) are bound globally
 * elsewhere, so they are intentionally NOT re-bound here. Registered in
 * bootstrap/providers.php.
 */
final class AccessServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        RoleRepository::class => EloquentRoleRepository::class,
        RoleReader::class => EloquentRoleRepository::class,
        PermissionRepository::class => EloquentPermissionRepository::class,
        PermissionReader::class => EloquentPermissionRepository::class,
        ModuleRepository::class => EloquentModuleRepository::class,
        ModuleReader::class => EloquentModuleRepository::class,
    ];

    public function register(): void
    {
        // Singleton so the per-request capability memo (its $cache) survives the
        // many capability checks the sidebar performs while building the menu.
        $this->app->singleton(UserCapabilities::class, EloquentUserCapabilities::class);
    }

    public function boot(): void
    {
        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(RoleWasCreated::eventName(), RoleWasCreated::class);
        $registry->register(PermissionWasCreated::eventName(), PermissionWasCreated::class);
        $registry->register(ModuleWasCreated::eventName(), ModuleWasCreated::class);
    }
}
