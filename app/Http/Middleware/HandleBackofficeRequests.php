<?php

namespace App\Http\Middleware;

use App\Constants\Heroicons;
use App\Services\Frontend\SidebarGenerator;
use App\Services\Frontend\UIElements\SidebarItems\SidebarHelloUser;
use App\Services\Frontend\UIElements\SidebarItems\SidebarLink;
use App\Services\Frontend\UIElements\SidebarItems\SidebarSeparator;
use Closure;
use Core\BoundedContext\Access\Application\Authorization\Authorize;
use Core\BoundedContext\Access\Domain\Permission\Capability;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Policy\PlanModules;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class HandleBackofficeRequests
{
    public function __construct(
        protected SidebarGenerator $sidebarGenerator,
        protected Authorize $authorize,
        protected PlanCatalogReader $planReader,
        protected PlanModules $modules = new PlanModules,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = (int) ($request->user()?->user_id ?? 0);
        $isAdmin = $userId !== 0 && ($this->authorize)($userId, Capability::BackofficeAdmin);

        $generator = $this->sidebarGenerator
            ->addSidebarItem(new SidebarHelloUser)
            ->addSidebarItem(
                new SidebarLink(
                    text: 'Dashboard',
                    href: route('backoffice.dashboard.index'),
                    iconComponent: Heroicons::HOME,
                    current: request()->routeIs('backoffice.dashboard.index'),
                )
            )
            ->addSidebarItem(new SidebarSeparator)
            ->addSidebarItem(
                new SidebarLink(
                    text: __('Habitos'),
                    href: route('backoffice.habits.index'),
                    iconComponent: Heroicons::BOOK_OPEN,
                    current: request()->routeIs('backoffice.habits.index'),
                )
            )
            ->addSidebarItem(
                new SidebarLink(
                    text: __('Calendario'),
                    href: route('backoffice.calendar.index'),
                    iconComponent: Heroicons::CALENDAR,
                    current: request()->routeIs('backoffice.calendar.index'),
                )
            )
            ->addSidebarItem(
                new SidebarLink(
                    text: __('Reporte diario'),
                    href: route('backoffice.daily-reports.index'),
                    iconComponent: Heroicons::CLIPBOARD,
                    current: request()->routeIs('backoffice.daily-reports.*'),
                )
            );

        // Atomic IA: superadmin always; otherwise only if the user's plan tier
        // allows the module (unlimited).
        if ($isAdmin || $this->planAllowsAtomicIa($userId)) {
            $generator->addSidebarItem(
                new SidebarLink(
                    text: 'Atomic IA',
                    href: route('backoffice.atomic-ia.index'),
                    iconComponent: Heroicons::CHAT_BUBBLE,
                    current: request()->routeIs('backoffice.atomic-ia.index'),
                )
            );
        }

        // Usuarios: management module — superadmin always; otherwise only with
        // the explicit Users.View capability.
        if ($isAdmin || ($userId !== 0 && ($this->authorize)($userId, Capability::UsersView))) {
            $generator->addSidebarItem(
                new SidebarLink(
                    text: __('Usuarios'),
                    href: route('backoffice.users.index'),
                    iconComponent: Heroicons::USERS,
                    current: request()->routeIs('backoffice.users.index'),
                )
            );
        }

        $generator
            ->addSidebarItem(new SidebarSeparator)
            ->addSidebarItem(
                new SidebarLink(
                    text: 'Cerrar sesión',
                    href: route('logout'),
                    iconComponent: Heroicons::LOGOUT,
                    current: false,
                )
            );

        view()->share('sidebarNavItems', $generator->getSidebarItems());

        // The header shows the current plan; tierOf defaults to free when the
        // user has no subscription row.
        view()->share(
            'currentPlanTier',
            $userId !== 0 ? $this->planReader->tierOf(UserId::from($userId))->value() : 'free',
        );

        return $next($request);
    }

    private function planAllowsAtomicIa(int $userId): bool
    {
        if ($userId === 0) {
            return false;
        }

        return $this->modules->allows($this->planReader->tierOf(UserId::from($userId)), 'atomic_ia');
    }
}
