<?php

namespace App\Http\Controllers\Api;

use App\Authorization\OrganizationAbilities;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddOrganizationMemberRequest;
use App\Http\Requests\Api\UpdateOrganizationMemberAbilitiesRequest;
use App\Http\Requests\Api\UpdateOrganizationProfileRequest;
use App\Models\ClinicAddress;
use App\Models\ClinicImage;
use App\Models\ClinicPhone;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    private function assertCanManageStaff(User $auth, Organization $organization): void
    {
        if ($auth->isPlatformAdmin()) {
            return;
        }

        $authMember = $auth->membershipFor($organization);
        if ($authMember === null) {
            abort(403);
        }

        $canManage = $authMember->is_owner || $authMember->can(OrganizationAbilities::CAN_MANAGE_STAFF);
        if (! $canManage) {
            abort(403, 'Missing permission to manage staff.');
        }
    }

    private function assertIsOwner(User $auth, Organization $organization): void
    {
        if ($auth->isPlatformAdmin()) {
            return;
        }

        $authMember = $auth->membershipFor($organization);
        if ($authMember === null || ! $authMember->is_owner) {
            abort(403, 'Only the clinic owner can add new staff members.');
        }
    }

    public function show(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();
        $member = $user->membershipFor($organization);

        $organization->load([
            'clinicAddresses',
            'clinicPhones',
            'clinicImages',
            'clinicTeamMembers',
            'services',
        ]);

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
            'profile' => $this->profile($organization),
        ]);
    }

    public function updateProfile(UpdateOrganizationProfileRequest $request, Organization $organization): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('name', $validated)) {
            $organization->name = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $organization->description = $validated['description'];
        }
        if (array_key_exists('email', $validated)) {
            $organization->email = $validated['email'];
        }
        if (array_key_exists('latitude', $validated)) {
            $organization->latitude = $validated['latitude'];
        }
        if (array_key_exists('longitude', $validated)) {
            $organization->longitude = $validated['longitude'];
        }
        if (array_key_exists('languages_spoken', $validated)) {
            $organization->languages_spoken = $validated['languages_spoken'];
        }
        if (array_key_exists('diplomas', $validated)) {
            $organization->diplomas = $validated['diplomas'];
        }
        if (array_key_exists('opening_hours', $validated)) {
            $organization->opening_hours = $validated['opening_hours'];
        }
        $organization->save();

        if (array_key_exists('service_ids', $validated)) {
            $organization->services()->sync($validated['service_ids'] ?? []);
        }

        if (array_key_exists('address', $validated)) {
            $line = trim((string) ($validated['address'] ?? ''));
            if ($line === '') {
                $organization->clinicAddresses()->where('is_primary', true)->delete();
            } else {
                $organization->clinicAddresses()->updateOrCreate(
                    ['is_primary' => true],
                    ['line' => $line],
                );
            }
        }

        if (array_key_exists('phone', $validated)) {
            $number = trim((string) ($validated['phone'] ?? ''));
            $primaryPhone = $organization->clinicPhones()->orderBy('sort_order')->first();
            if ($number === '') {
                $primaryPhone?->delete();
            } elseif ($primaryPhone) {
                $primaryPhone->update(['number' => $number]);
            } else {
                $organization->clinicPhones()->create(['number' => $number, 'label' => 'Primary']);
            }
        }

        $organization->load(['clinicAddresses', 'clinicPhones', 'clinicImages', 'clinicTeamMembers', 'services']);

        return response()->json([
            'profile' => $this->profile($organization),
        ]);
    }

    public function updateMemberAbilities(
        UpdateOrganizationMemberAbilitiesRequest $request,
        Organization $organization,
        User $memberUser,
    ): JsonResponse {
        $auth = $request->user();
        $this->assertCanManageStaff($auth, $organization);

        $target = $organization->members()->where('user_id', $memberUser->id)->firstOrFail();

        if ($target->is_owner && ! $auth->isPlatformAdmin()) {
            abort(403, 'Organization owner abilities can only be changed by a platform admin.');
        }

        $validated = $request->validated();

        if (array_key_exists('abilities', $validated)) {
            $incoming = $validated['abilities'];
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
        }

        if (array_key_exists('title', $validated)) {
            $target->title = $validated['title'];
        }

        $target->save();

        return response()->json([
            'user' => [
                'id' => $memberUser->id,
                'name' => $memberUser->name,
                'email' => $memberUser->email,
            ],
            'title' => $target->title,
            'abilities' => $target->effectiveAbilities(),
        ]);
    }

    public function members(Request $request, Organization $organization): JsonResponse
    {
        $this->assertCanManageStaff($request->user(), $organization);

        $members = $organization->members()
            ->with('user')
            ->get()
            ->map(fn (OrganizationMember $member) => [
                'user' => [
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                ],
                'is_owner' => $member->is_owner,
                'title' => $member->title,
                'abilities' => $member->effectiveAbilities(),
            ])
            ->values();

        return response()->json([
            'members' => $members,
        ]);
    }

    public function addMember(AddOrganizationMemberRequest $request, Organization $organization): JsonResponse
    {
        $auth = $request->user();
        $validated = $request->validated();
        $email = $validated['email'];
        $wantsOwner = (bool) ($validated['is_owner'] ?? false);

        if ($wantsOwner) {
            if (! $auth->isPlatformAdmin()) {
                abort(403, 'Only a platform admin can add an owner.');
            }
        } else {
            $this->assertIsOwner($auth, $organization);
        }

        $existingUser = User::query()->where('email', $email)->first();

        if ($organization->members()->whereHas('user', fn ($q) => $q->where('email', $email))->exists()) {
            abort(422, 'This person is already a staff member of this organization.');
        }

        if ($existingUser) {
            $targetUser = $existingUser;
            if ($targetUser->role === User::ROLE_PATIENT) {
                $targetUser->role = User::ROLE_STAFF;
                $targetUser->save();
            }
        } else {
            if (blank($validated['name'] ?? null) || blank($validated['password'] ?? null)) {
                abort(422, 'A name and password are required to create a new staff account.');
            }

            $targetUser = User::query()->create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => $validated['password'],
                'role' => User::ROLE_STAFF,
            ]);
            $targetUser->must_change_password = true;
            $targetUser->save();
        }

        $abilities = OrganizationAbilities::normalize($validated['abilities'] ?? null, $wantsOwner);

        $member = $organization->members()->create([
            'user_id' => $targetUser->id,
            'is_owner' => $wantsOwner,
            'title' => $validated['title'] ?? null,
            'abilities' => $abilities,
        ]);

        return response()->json([
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
            'is_owner' => $member->is_owner,
            'title' => $member->title,
            'abilities' => $member->effectiveAbilities(),
        ], 201);
    }

    public function removeMember(Request $request, Organization $organization, User $memberUser): JsonResponse
    {
        $auth = $request->user();
        $this->assertCanManageStaff($auth, $organization);

        $target = $organization->members()->where('user_id', $memberUser->id)->firstOrFail();

        if ($target->is_owner) {
            abort(422, 'The organization owner cannot be removed.');
        }

        if ($memberUser->id === $auth->id) {
            abort(422, 'You cannot remove yourself from the organization.');
        }

        $target->delete();

        if ($memberUser->role === User::ROLE_STAFF && $memberUser->organizationMembers()->count() === 0) {
            $memberUser->role = User::ROLE_PATIENT;
            $memberUser->save();
        }

        return response()->json(['message' => 'Staff member removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(Organization $organization): array
    {
        $imagesByType = $organization->clinicImages->groupBy('type');
        $primaryAddress = $organization->clinicAddresses->firstWhere('is_primary', true)
            ?? $organization->clinicAddresses->first();
        $primaryPhone = $organization->clinicPhones->first();

        $profile = [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'type' => $organization->type,
            'description' => $organization->description,
            'email' => $organization->email,
            'phone' => $primaryPhone?->number,
            'address' => $primaryAddress?->line,
            'latitude' => $organization->latitude,
            'longitude' => $organization->longitude,
            'certified' => $organization->certified,
            'languages_spoken' => $organization->languages_spoken ?? [],
            'diplomas' => $organization->diplomas ?? [],
            'opening_hours' => $organization->opening_hours ?? [],
            'service_ids' => $organization->services->pluck('id')->values()->all(),
            'logo' => $this->imageSummary($imagesByType->get(ClinicImage::TYPE_LOGO)?->first()),
            'featured_image' => $this->imageSummary($imagesByType->get(ClinicImage::TYPE_HERO)?->first()),
            'gallery' => ($imagesByType->get(ClinicImage::TYPE_GALLERY) ?? collect())
                ->map(fn (ClinicImage $img) => $this->imageSummary($img))
                ->values()->all(),
            'team_members' => $organization->clinicTeamMembers->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'title' => $m->title,
                'image_url' => $m->image_url,
            ])->values()->all(),
        ];

        $profile['completeness_percent'] = $this->completenessPercent($profile);

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function completenessPercent(array $profile): int
    {
        $hasOpeningHours = collect($profile['opening_hours'])
            ->contains(fn ($day) => empty($day['closed'])
                && filled($day['open'] ?? null)
                && filled($day['close'] ?? null));

        $checks = [
            filled($profile['description']),
            filled($profile['email']),
            filled($profile['phone']),
            filled($profile['address']),
            filled($profile['latitude']) && filled($profile['longitude']),
            ! empty($profile['languages_spoken']),
            ! empty($profile['diplomas']),
            ! empty($profile['service_ids']),
            $hasOpeningHours,
            $profile['logo'] !== null,
            $profile['featured_image'] !== null,
            ! empty($profile['gallery']),
            ! empty($profile['team_members']),
        ];

        $total = count($checks);
        $met = count(array_filter($checks));

        return $total > 0 ? (int) round($met / $total * 100) : 0;
    }

    /**
     * @return array{id: int, url: string}|null
     */
    private function imageSummary(?ClinicImage $image): ?array
    {
        if ($image === null) {
            return null;
        }

        return ['id' => $image->id, 'url' => $image->url];
    }
}
