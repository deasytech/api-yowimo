<?php

namespace App\Models;

use App\Enums\PackCategory;
use Database\Factories\PackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'game_type_id',
    'slug',
    'name',
    'emoji',
    'tag',
    'category',
    'description',
    'price',
    'truths_count',
    'dares_count',
    'cards_count',
    'cover_image_url',
    'gradient',
    'is_featured',
    'is_active',
    'sort_order',
])]
class Pack extends Model
{
    /** @use HasFactory<PackFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PackCategory::class,
            'gradient' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'price' => 'integer',
            'truths_count' => 'integer',
            'dares_count' => 'integer',
            'cards_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GameType, $this>
     */
    public function gameType(): BelongsTo
    {
        return $this->belongsTo(GameType::class);
    }

    /**
     * @return HasMany<PackCard, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(PackCard::class);
    }

    /**
     * @return HasMany<Party, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }
}
