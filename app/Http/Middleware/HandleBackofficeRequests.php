<?php

namespace App\Http\Middleware;

use App\Constants\Heroicons;
use App\Services\Frontend\SidebarGenerator;
use App\Services\Frontend\UIElements\SidebarItems\SidebarHelloUser;
use App\Services\Frontend\UIElements\SidebarItems\SidebarLink;
use App\Services\Frontend\UIElements\SidebarItems\SidebarSeparator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class HandleBackofficeRequests
{
    public function __construct(protected readonly SidebarGenerator $sidebarGenerator)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        view()->share('sidebarNavItems', $this->sidebarGenerator
            ->addSidebarItem(new SidebarHelloUser())
            ->addSidebarItem(
                new SidebarLink(
                    text: 'Dashboard',
                    href: route('backoffice.dashboard.index'),
                    iconComponent: Heroicons::HOME,
                    current: request()->routeIs('backoffice.dashboard.index'),
                )
            )
            ->addSidebarItem(new SidebarSeparator())
            ->addSidebarItem(
                new SidebarLink(
                    text: __('Habitos'),
                    href: route('backoffice.habits.index'),
                    iconComponent: Heroicons::BOOK_OPEN,
                    current: request()->routeIs('backoffice.habits.index'),
                )
            )
            ->addSidebarItem(new SidebarSeparator())
            ->addSidebarItem(
                new SidebarLink(
                    text: 'Cerrar sesión',
                    href: route('logout'),
                    iconComponent: Heroicons::LOGOUT,
                    current: false,
                )
            )->getSidebarItems()
        );

        return $next($request);
    }
}
