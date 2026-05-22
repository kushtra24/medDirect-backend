<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'date_of_birth', 'blood_type', 'phone', 'address', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_PATIENT = 'patient';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
        ];
    }

    public function isPatient(): bool
    {
        return $this->role === self::ROLE_PATIENT;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function organizationMembers(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->withPivot(['is_owner', 'abilities'])
            ->withTimestamps();
    }

    /**
     * Membership for a single organization, if any.
     */
    public function membershipFor(Organization $organization): ?OrganizationMember
    {
        return $this->organizationMembers()
            ->where('organization_id', $organization->id)
            ->first();
    }

    public function canAccessOrganization(Organization $organization): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return $this->membershipFor($organization) !== null;
    }

    public function hasOrganizationAbility(Organization $organization, string $ability): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        $member = $this->membershipFor($organization);

        if ($member === null) {
            return false;
        }

        return $member->can($ability);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}
