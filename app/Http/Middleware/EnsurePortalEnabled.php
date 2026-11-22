<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the logged-in portal (login, registration, dashboard, teams, settings)
 * unless APP_PORTAL_ENABLED=true. Production keeps it off until the portal is finished.
 */
class EnsurePortalEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.portal_enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
