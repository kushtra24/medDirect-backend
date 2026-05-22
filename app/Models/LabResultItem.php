<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResultItem extends Model
{
    public const STATUS_NORMAL = 'normal';

    public const STATUS_HIGH = 'high';

    public const STATUS_LOW = 'low';

    protected $fillable = [
        'lab_result_report_id',
        'test_name',
        'value',
        'status',
        'reference_point',
        'sort_order',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(LabResultReport::class, 'lab_result_report_id');
    }
}
