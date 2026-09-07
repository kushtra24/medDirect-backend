<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientUpdateNotice extends Model
{
    protected $fillable = [
        'patient_id',
        'organization_id',
        'user_id',
        'changed_fields',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'read_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
