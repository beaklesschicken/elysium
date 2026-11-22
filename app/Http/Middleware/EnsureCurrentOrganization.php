<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes sure the signed-in user has a current organization they still belong to.
 * Falls back to their first organization; otherwise site admins go create one and
 * everyone else sees that they're waiting for an invitation.
 */
class EnsureCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $user->belongsToOrganization($user->current_organization_id ?? 0)) {
            $firstOrganization = $user->organizations()->orderBy('organizations.id')->first();

            if ($firstOrganization === null) {
                return redirect()->route($user->isSiteAdmin() ? 'organizations.create' : 'organizations.none');
            }

            $user->switchOrganization($firstOrganization);
        }

        return $next($request);
    }
}
