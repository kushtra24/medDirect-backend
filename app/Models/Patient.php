<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

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

    /**
     * Named to avoid colliding with the legacy free-text `allergies` column —
     * accessing `$patient->allergies` must keep returning that column's raw value.
     */
    public function allergyRecords(): HasMany
    {
        return $this->hasMany(PatientAllergy::class)->orderByDesc('noted_at')->orderByDesc('id');
    }

    public function problems(): HasMany
    {
        return $this->hasMany(PatientProblem::class)->orderByDesc('onset_date')->orderByDesc('id');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class)->orderByDesc('start_date')->orderByDesc('id');
    }

    public function immunizations(): HasMany
    {
        return $this->hasMany(PatientImmunization::class)->orderByDesc('administered_at')->orderByDesc('id');
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(PatientVital::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PatientOrder::class)->orderByDesc('created_at');
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
