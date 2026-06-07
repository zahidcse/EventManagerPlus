<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Installer\InstallationStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent re-running the installer after the lock file exists.
 */
final class BlockInstallerAfterCompletion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallationStatus::completed()) {
            return redirect('/');
        }

        return $next($request);
    }
}
