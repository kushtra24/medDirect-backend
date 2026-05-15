<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if ($organization === null) {
            abort(Response::HTTP_BAD_REQUEST, 'Missing organization route binding.');
        }

        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->canAccessOrganization($organization)) {
            abort(Response::HTTP_FORBIDDEN, 'You are not a member of this organization.');
        }

        return $next($request);
    }
}
