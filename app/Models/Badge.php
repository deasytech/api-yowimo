<?php

namespace App\Models;

use App\Enums\BadgeKey;
use Database\Factories\BadgeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'name',
    'description',
    'icon',
])]
class Badge extends Model
{
    /** @use HasFactory<BadgeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => BadgeKey::class,
        ];
    }

    /**
     * @return HasMany<UserBadge, $this>
     */
    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
