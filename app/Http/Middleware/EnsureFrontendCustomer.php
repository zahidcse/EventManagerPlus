<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFrontendCustomer
{
    /**
     * Staff (admin) accounts use the admin panel, not customer account pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->canAccessAdminPanel()) {
            return redirect()->route(
                \App\Support\Admin\AdminModules::defaultLandingRouteName($user),
            );
        }

        return $next($request);
    }
}
