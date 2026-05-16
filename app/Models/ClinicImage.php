<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicImage extends Model
{
    public const TYPE_HERO = 'hero';

    public const TYPE_LOGO = 'logo';

    public const TYPE_MAP = 'map';

    public const TYPE_CARD = 'card';

    protected $fillable = [
        'organization_id',
        'url',
        'type',
        'sort_order',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
