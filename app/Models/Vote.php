<?php

namespace App\Models;

use App\Enums\VoteCategory;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'turn_id',
    'voter_id',
    'category',
])]
class Vote extends Model
{
    /** @use HasFactory<VoteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => VoteCategory::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Votes are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('Votes are append-only and cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<Turn, $this>
     */
    public function turn(): BelongsTo
    {
        return $this->belongsTo(Turn::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_id');
    }
}
