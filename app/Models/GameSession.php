<?php

namespace App\Models;

use App\Enums\GameSessionStatus;
use Database\Factories\GameSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'party_id',
    'host_id',
    'pack_id',
    'status',
    'rounds_count',
    'current_round_number',
    'turn_order',
    'current_turn_index',
    'started_at',
    'ended_at',
])]
class GameSession extends Model
{
    /** @use HasFactory<GameSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameSessionStatus::class,
            'rounds_count' => 'integer',
            'current_round_number' => 'integer',
            'turn_order' => 'array',
            'current_turn_index' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Party, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * @return HasMany<Round, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    /**
     * @return HasMany<Turn, $this>
     */
    public function turns(): HasMany
    {
        return $this->hasMany(Turn::class);
    }

    public function currentRound(): ?Round
    {
        return $this->rounds()->where('number', $this->current_round_number)->first();
    }

    public function currentTurn(): ?Turn
    {
        return $this->turns()
            ->where('round_id', $this->currentRound()?->id)
            ->where('position', $this->current_turn_index)
            ->first();
    }
}
