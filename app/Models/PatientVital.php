<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientVital extends Model
{
    protected $fillable = [
        'patient_id',
        'organization_id',
        'created_by_user_id',
        'recorded_at',
        'height_cm',
        'weight_kg',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'heart_rate',
        'temperature_c',
        'respiratory_rate',
        'oxygen_saturation',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'height_cm' => 'float',
            'weight_kg' => 'float',
            'temperature_c' => 'float',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function bmi(): ?float
    {
        if (! $this->height_cm || ! $this->weight_kg) {
            return null;
        }

        $heightMeters = $this->height_cm / 100;

        return round($this->weight_kg / ($heightMeters * $heightMeters), 1);
    }
}
