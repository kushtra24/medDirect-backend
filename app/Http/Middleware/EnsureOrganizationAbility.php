<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $organization = $request->route('organization');

        if ($organization === null) {
            abort(Response::HTTP_BAD_REQUEST, 'Missing organization route binding.');
        }

        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->hasOrganizationAbility($organization, $ability)) {
            abort(Response::HTTP_FORBIDDEN, 'Missing required organization permission: '.$ability);
        }

        return $next($request);
    }
}
