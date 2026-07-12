<?php

namespace App\Models;

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'host_id',
    'game_type_id',
    'pack_id',
    'room_code',
    'title',
    'description',
    'mode',
    'visibility',
    'status',
    'max_players',
    'players_count',
    'likes_count',
    'starts_at',
    'location',
    'tags',
    'cover_image_url',
    'gradient',
    'is_sponsored',
    'sponsor_name',
])]
class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => PartyMode::class,
            'visibility' => PartyVisibility::class,
            'status' => PartyStatus::class,
            'max_players' => 'integer',
            'players_count' => 'integer',
            'likes_count' => 'integer',
            'starts_at' => 'datetime',
            'location' => 'array',
            'tags' => 'array',
            'gradient' => 'array',
            'is_sponsored' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * @return BelongsTo<GameType, $this>
     */
    public function gameType(): BelongsTo
    {
        return $this->belongsTo(GameType::class);
    }

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * @return HasMany<PartyLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PartyLike::class);
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (array_key_exists('viewer_has_liked', $this->attributes)) {
            return (bool) $this->attributes['viewer_has_liked'];
        }

        return $this->relationLoaded('likes')
            ? $this->likes->contains('user_id', $user->id)
            : $this->likes()->where('user_id', $user->id)->exists();
    }
}
