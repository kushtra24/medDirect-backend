<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicTeamMember;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClinicTeamMemberController extends Controller
{
    public function store(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('clinics/'.$organization->id.'/team', 'public');
            $imageUrl = Storage::disk('public')->url($path);
        }

        $sortOrder = (int) $organization->clinicTeamMembers()->max('sort_order') + 1;

        $member = $organization->clinicTeamMembers()->create([
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
        ]);

        return response()->json([
            'team_member' => [
                'id' => $member->id,
                'name' => $member->name,
                'title' => $member->title,
                'image_url' => $member->image_url,
            ],
        ], 201);
    }

    public function destroy(Request $request, Organization $organization, ClinicTeamMember $teamMember): JsonResponse
    {
        if ($teamMember->organization_id !== $organization->id) {
            abort(404);
        }

        if ($teamMember->image_url) {
            $prefix = rtrim(Storage::disk('public')->url(''), '/').'/';
            if (str_starts_with($teamMember->image_url, $prefix)) {
                Storage::disk('public')->delete(substr($teamMember->image_url, strlen($prefix)));
            }
        }

        $teamMember->delete();

        return response()->json(['message' => 'ok']);
    }
}
