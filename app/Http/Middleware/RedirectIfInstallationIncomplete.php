<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Installer\InstallationStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends visitors to the web installer until installation is finished.
 */
final class RedirectIfInstallationIncomplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallationStatus::completed()) {
            return $next($request);
        }

        $path = $request->path();

        if ($path === 'up' || $path === '_ignition/health-check' || str_starts_with($path, 'install')) {
            return $next($request);
        }

        return redirect('/install');
    }
}
