<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use App\Services\PatientClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly PatientClaimService $claimService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $fullName = trim($validated['name'].' '.($validated['surname'] ?? ''));

        $user = User::query()->create([
            'name' => $fullName !== '' ? $fullName : $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);
        $user->role = User::ROLE_PATIENT;
        $user->save();

        $claimed = $this->claimService->claimForUser($user);

        $user->load(['organizationMembers.organization']);

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'user' => new AuthUserResource($user),
            'token' => $token,
            'claimed_records' => $claimed->count(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user->tokens()->delete();
        $user->load(['organizationMembers.organization']);

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'user' => new AuthUserResource($user),
            'token' => $token,
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['organizationMembers.organization']);

        return response()->json([
            'user' => new AuthUserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'ok']);
    }
}
