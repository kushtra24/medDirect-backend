<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExaminationResultItem extends Model
{
    public const STATUS_NORMAL = 'normal';

    public const STATUS_HIGH = 'high';

    public const STATUS_LOW = 'low';

    protected $fillable = [
        'examination_result_report_id',
        'finding_name',
        'value',
        'status',
        'reference_point',
        'sort_order',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ExaminationResultReport::class, 'examination_result_report_id');
    }
}