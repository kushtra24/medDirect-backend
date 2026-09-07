<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'created_by_user_id',
        'first_name',
        'surname',
        'date_of_birth',
        'gender',
        'national_id',
        'insurance_provider',
        'policy_number',
        'blood_group',
        'allergies',
        'chronic_conditions',
        'phone',
        'email',
        'access_pin',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'claimed_at' => 'datetime',
            'access_pin' => 'encrypted',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function labResultReports(): HasMany
    {
        return $this->hasMany(LabResultReport::class)->orderByDesc('reported_at')->orderByDesc('id');
    }

    public function examinationResultReports(): HasMany
    {
        return $this->hasMany(ExaminationResultReport::class)->orderByDesc('reported_at')->orderByDesc('id');
    }

    public function updateNotices(): HasMany
    {
        return $this->hasMany(PatientUpdateNotice::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->surname);
    }

    public function isClaimed(): bool
    {
        return $this->user_id !== null;
    }
}
