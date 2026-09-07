<?php

namespace App\Models;

use App\Authorization\OrganizationAbilities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMember extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'is_owner',
        'title',
        'abilities',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'abilities' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, bool>
     */
    public function effectiveAbilities(): array
    {
        return OrganizationAbilities::normalize($this->abilities, $this->is_owner);
    }

    public function can(string $ability): bool
    {
        return $this->effectiveAbilities()[$ability] ?? false;
    }
}
