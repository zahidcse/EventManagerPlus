<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\RedirectIfInstallationIncomplete::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            $path = $request->path();
            if ($path === 'admin' || str_starts_with($path, 'admin/')) {
                return route('admin.login');
            }

            return route('login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            $path = $request->path();
            $inAdminUrls = $path === 'admin' || str_starts_with($path, 'admin/');
            $user = $request->user();

            if ($inAdminUrls && $user?->canAccessAdminPanel()) {
                return route(\App\Support\Admin\AdminModules::defaultLandingRouteName($user));
            }
            if ($inAdminUrls && $user !== null && ! $user->canAccessAdminPanel()) {
                return route('home');
            }

            return $user?->canAccessAdminPanel()
                ? route(\App\Support\Admin\AdminModules::defaultLandingRouteName($user))
                : route('home');
        });
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'events/booking/sslcommerz/success',
            'events/booking/sslcommerz/fail',
            'events/booking/sslcommerz/cancel',
            'events/booking/sslcommerz/ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
