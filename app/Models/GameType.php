<?php

namespace App\Models;

use App\Enums\GameIntensity;
use Database\Factories\GameTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'emoji',
    'tagline',
    'audience',
    'intensity',
    'cost',
    'image_url',
    'default_pack_id',
    'gradient',
    'is_active',
    'sort_order',
])]
class GameType extends Model
{
    /** @use HasFactory<GameTypeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'intensity' => GameIntensity::class,
            'gradient' => 'array',
            'is_active' => 'boolean',
            'cost' => 'integer',
            'default_pack_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The deck a party inherits when this game is picked without one.
     *
     * @return BelongsTo<Pack, $this>
     */
    public function defaultPack(): BelongsTo
    {
        return $this->belongsTo(Pack::class, 'default_pack_id');
    }

    /**
     * @return HasMany<Pack, $this>
     */
    public function packs(): HasMany
    {
        return $this->hasMany(Pack::class);
    }

    /**
     * @return HasMany<Party, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }
}
