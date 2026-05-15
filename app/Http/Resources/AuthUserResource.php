<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $members = $this->relationLoaded('organizationMembers')
            ? $this->organizationMembers
            : collect();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'blood_type' => $this->blood_type,
            'phone' => $this->phone,
            'address' => $this->address,
            'avatar_url' => $this->avatar_path
                ? Storage::disk('public')->url($this->avatar_path)
                : null,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'organizations' => $members->map(function ($member) {
                $org = $member->organization;

                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'type' => $org->type,
                    'is_owner' => $member->is_owner,
                    'abilities' => $member->effectiveAbilities(),
                ];
            })->values(),
        ];
    }
}
