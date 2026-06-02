<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware(['backoffice', 'auth', 'verified'])
                ->prefix('backoffice')
                ->as('backoffice.')
                ->group(base_path('routes/backoffice.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->appendToGroup('backoffice', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleBackofficeRequests::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Core\BoundedContext\Habits\Domain\Exceptions\HabitNotFound $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 404);
            }

            return response($e->getMessage(), 404);
        });
        $exceptions->render(function (
            \Core\BoundedContext\HabitSchedules\Domain\Exceptions\HabitScheduleNotFound $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 404);
            }

            return response($e->getMessage(), 404);
        });
        $exceptions->render(function (
            \Core\BoundedContext\HabitOccurrences\Domain\Exceptions\HabitOccurrenceNotFound $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 404);
            }

            return response($e->getMessage(), 404);
        });
        $exceptions->render(function (
            \Core\BoundedContext\DailyReports\Domain\Exceptions\DailyReportNotFound $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 404);
            }

            return response($e->getMessage(), 404);
        });
        $exceptions->render(function (
            \Core\BoundedContext\DailyReports\Domain\Exceptions\DailyReportEntryNotFound $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 404);
            }

            return response($e->getMessage(), 404);
        });
        // Cualquier excepción de dominio que implemente ProvidesValidationErrors
        // se mapea a 422 por campo. Añadir nuevas validaciones de dominio NO
        // requiere tocar este archivo: basta con implementar el contrato.
        $exceptions->render(function (
            \Core\Shared\Domain\ProvidesValidationErrors $e,
            \Illuminate\Http\Request $request,
        ) {
            $errors = $e->validationErrors();
            $message = collect($errors)->flatten()->first() ?? $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => $errors], 422);
            }

            return back()->withErrors($errors)->withInput();
        });
    })->create();
