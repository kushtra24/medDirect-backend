<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientProblem extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'patient_id',
        'organization_id',
        'created_by_user_id',
        'condition',
        'status',
        'onset_date',
        'resolved_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'onset_date' => 'date',
            'resolved_date' => 'date',
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
