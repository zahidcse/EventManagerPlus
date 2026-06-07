<?php

namespace App\Http\Middleware;

use App\Support\Admin\AdminModules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canAccessAdminPanel()) {
            if ($request->expectsJson()) {
                abort(403, 'Admin access required.');
            }

            return redirect()->route('home');
        }

        $routeName = $request->route()?->getName();

        if ($routeName !== null && in_array($routeName, AdminModules::unrestrictedRouteNames(), true)) {
            return $next($request);
        }

        $module = AdminModules::moduleForRoute($routeName);

        if ($module !== null && ! $user->canAccessAdminModule($module)) {
            if ($request->expectsJson()) {
                abort(403, 'You do not have permission to access this module.');
            }

            $fallback = AdminModules::defaultLandingRouteName($user);

            if ($routeName === $fallback) {
                return redirect()
                    ->route('admin.profile.edit')
                    ->with('error', 'Your account does not have permission to access the admin panel modules.');
            }

            return redirect()
                ->route($fallback)
                ->with('error', 'You do not have permission to access that section.');
        }

        return $next($request);
    }
}
