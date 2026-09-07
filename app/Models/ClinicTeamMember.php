<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicTeamMember extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'title',
        'image_url',
        'sort_order',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
