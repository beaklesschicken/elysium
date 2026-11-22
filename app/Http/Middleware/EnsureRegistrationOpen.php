<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides public sign-up while the portal is invite-only (APP_REGISTRATION_OPEN=false).
 */
class EnsureRegistrationOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('app.registration_open'), 404);

        return $next($request);
    }
}
