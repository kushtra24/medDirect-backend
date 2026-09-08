<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientOrder extends Model
{
    public const TYPE_LAB = 'lab';

    public const TYPE_EXAMINATION = 'examination';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses that still represent open, unfulfilled work.
     */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_IN_PROGRESS];

    protected $fillable = [
        'patient_id',
        'organization_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'type',
        'status',
        'reason',
        'lab_result_report_id',
        'examination_result_report_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function labResultReport(): BelongsTo
    {
        return $this->belongsTo(LabResultReport::class);
    }

    public function examinationResultReport(): BelongsTo
    {
        return $this->belongsTo(ExaminationResultReport::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
