<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthUserResource;
use App\Services\PatientChangeNotifier;
use App\Services\PatientClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserProfileController extends Controller
{
    public function __construct(
        private readonly PatientClaimService $claimService,
        private readonly PatientChangeNotifier $changeNotifier,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'blood_type' => ['nullable', 'string', 'max:16'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'national_id' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();
        $user->fill($validated);
        $user->save();

        if ($user->isPatient()) {
            $this->claimService->claimForUser($user);
            $this->changeNotifier->notifyForUser($user);
        }

        $user->load(['organizationMembers.organization']);

        return response()->json(['user' => new AuthUserResource($user)]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->must_change_password = false;
        $user->save();

        return response()->json(['message' => 'Password updated.']);
    }

    public function avatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        $user->avatar_path = $path;
        $user->save();
        $user->load(['organizationMembers.organization']);

        return response()->json(['user' => new AuthUserResource($user)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isPatient()) {
            return response()->json([
                'message' => 'Only patient accounts can be deleted here.',
            ], 403);
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'ok']);
    }
}
