<?php

namespace App\Http\Controllers\Api;

use App\Authorization\OrganizationAbilities;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateOrganizationMemberAbilitiesRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function show(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();
        $member = $user->membershipFor($organization);

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
            ],
            'membership' => $member ? [
                'is_owner' => $member->is_owner,
                'abilities' => $member->effectiveAbilities(),
            ] : null,
        ]);
    }

    /**
     * Example protected action: create patient (requires canCreatePatient).
     */
    public function storePatientPlaceholder(Request $request, Organization $organization): JsonResponse
    {
        return response()->json([
            'message' => 'Authorized for canCreatePatient. Replace with real patient creation.',
            'organization_id' => $organization->id,
        ], 201);
    }

    public function updateMemberAbilities(
        UpdateOrganizationMemberAbilitiesRequest $request,
        Organization $organization,
        User $memberUser,
    ): JsonResponse {
        $auth = $request->user();

        if (! $auth->isPlatformAdmin()) {
            $authMember = $auth->membershipFor($organization);
            if ($authMember === null) {
                abort(403);
            }
            $canManage = $authMember->is_owner || $authMember->can(OrganizationAbilities::CAN_MANAGE_STAFF);
            if (! $canManage) {
                abort(403, 'Missing permission to manage staff.');
            }
        }

        $target = $organization->members()->where('user_id', $memberUser->id)->firstOrFail();

        if ($target->is_owner && ! $auth->isPlatformAdmin()) {
            abort(403, 'Organization owner abilities can only be changed by a platform admin.');
        }

        $incoming = $request->validated('abilities');
        $existing = is_array($target->abilities) ? $target->abilities : [];
        $next = [];
        foreach (OrganizationAbilities::keys() as $key) {
            if (array_key_exists($key, $incoming)) {
                $next[$key] = (bool) $incoming[$key];
            } elseif (array_key_exists($key, $existing)) {
                $next[$key] = (bool) $existing[$key];
            } else {
                $next[$key] = false;
            }
        }
        $target->abilities = $next;
        $target->save();

        return response()->json([
            'user' => [
                'id' => $memberUser->id,
                'name' => $memberUser->name,
                'email' => $memberUser->email,
            ],
            'abilities' => $target->effectiveAbilities(),
        ]);
    }
}
