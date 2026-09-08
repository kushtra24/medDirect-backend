<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientImmunization extends Model
{
    protected $fillable = [
        'patient_id',
        'organization_id',
        'created_by_user_id',
        'vaccine',
        'dose_number',
        'administered_at',
        'administered_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'administered_at' => 'date',
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
}
