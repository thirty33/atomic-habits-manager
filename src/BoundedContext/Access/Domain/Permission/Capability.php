<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Domain\Permission;

/**
 * Single source of truth for the capability codes this application authorizes
 * against. The literal code strings live ONLY here: change a case value and it
 * changes everywhere (authorization checks, sidebar gating and the catalog
 * seeder), because every authorization check passes a Capability, never a raw
 * string.
 *
 * The app-specific metadata of each code — which module it belongs to and its
 * human label — stays in the seeder, not here. The module code each capability
 * belongs to is the segment before the first dot of its value.
 */
enum Capability: string
{
    // Backoffice
    case BackofficeAccess = 'backoffice.access';
    case BackofficeAdmin = 'backoffice.admin';

    // Habits (module habits)
    case HabitsView = 'habits.view';
    case HabitsCreate = 'habits.create';
    case HabitsUpdate = 'habits.update';
    case HabitsDelete = 'habits.delete';

    // Calendar (module calendar)
    case CalendarView = 'calendar.view';

    // Reports (module reports)
    case ReportsView = 'reports.view';
    case ReportsUpdate = 'reports.update';

    // Atomic IA (module atomic_ia)
    case AtomicIaUse = 'atomic_ia.use';

    // Users (module users)
    case UsersView = 'users.view';
    case UsersActivate = 'users.activate';
    case UsersDeactivate = 'users.deactivate';

    // Roles (module roles)
    case RolesView = 'roles.view';
    case RolesManage = 'roles.manage';

    // Permissions (module permissions)
    case PermissionsView = 'permissions.view';
    case PermissionsManage = 'permissions.manage';

    // Modules (module modules)
    case ModulesView = 'modules.view';
    case ModulesManage = 'modules.manage';

    /**
     * The module code this capability belongs to (segment before the first dot).
     */
    public function moduleCode(): string
    {
        return explode('.', $this->value, 2)[0];
    }
}
