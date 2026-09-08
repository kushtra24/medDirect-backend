<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedication extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISCONTINUED = 'discontinued';

    protected $fillable = [
        'patient_id',
        'organization_id',
        'created_by_user_id',
        'name',
        'dosage',
        'frequency',
        'route',
        'status',
        'start_date',
        'end_date',
        'prescriber',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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
