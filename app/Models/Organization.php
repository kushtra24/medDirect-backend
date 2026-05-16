<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    public const TYPE_CLINIC = 'clinic';

    public const TYPE_LAB = 'lab';

    public const TYPE_POLICLINIC = 'policlinic';

    /** @var list<string> */
    public const MEDICAL_INSTITUTE_TYPES = [
        self::TYPE_CLINIC,
        self::TYPE_LAB,
        self::TYPE_POLICLINIC,
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'created_by_user_id',
        'description',
        'opening_hours',
        'health_insurances',
        'languages_spoken',
        'specialties',
        'diplomas',
        'allows_appointments',
        'certified',
        'rating',
        'neighborhood',
        'distance_km',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'health_insurances' => 'array',
            'languages_spoken' => 'array',
            'specialties' => 'array',
            'diplomas' => 'array',
            'allows_appointments' => 'boolean',
            'certified' => 'boolean',
            'rating' => 'float',
            'distance_km' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_members')
            ->withPivot(['is_owner', 'abilities'])
            ->withTimestamps();
    }

    public function clinicAddresses(): HasMany
    {
        return $this->hasMany(ClinicAddress::class)->orderBy('sort_order');
    }

    public function clinicPhones(): HasMany
    {
        return $this->hasMany(ClinicPhone::class)->orderBy('sort_order');
    }

    public function clinicImages(): HasMany
    {
        return $this->hasMany(ClinicImage::class)->orderBy('sort_order');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'clinic_service')
            ->withTimestamps();
    }

    public function scopeClinics(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CLINIC);
    }

    public function scopeMedicalInstitutes(Builder $query): Builder
    {
        return $query->whereIn('type', self::MEDICAL_INSTITUTE_TYPES);
    }

    public function scopeWithPublicProfile(Builder $query): Builder
    {
        return $query->whereNotNull('description');
    }

    public function isClinic(): bool
    {
        return $this->type === self::TYPE_CLINIC;
    }

    public function isMedicalInstitute(): bool
    {
        return in_array($this->type, self::MEDICAL_INSTITUTE_TYPES, true);
    }

    public function hasPublicProfile(): bool
    {
        return $this->description !== null;
    }

    public function isOpenNow(): bool
    {
        if ($this->opening_hours === null) {
            return false;
        }

        $now = now();

        foreach ($this->opening_hours as $slot) {
            if (! empty($slot['closed'])) {
                if ($now->isSunday() && str_contains(strtolower((string) ($slot['days'] ?? '')), 'sun')) {
                    return false;
                }

                continue;
            }

            if ($now->isSunday()) {
                continue;
            }

            $hour = (int) $now->format('G');

            return $hour >= 8 && $hour < 18;
        }

        return false;
    }

    public function nextOpenTimeLabel(): ?string
    {
        if ($this->isOpenNow()) {
            return null;
        }

        return now()->isSunday() ? '8AM Mon' : '8AM';
    }
}
