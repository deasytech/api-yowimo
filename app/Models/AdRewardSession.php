<?php

namespace App\Models;

use App\Enums\AdRewardSessionStatus;
use Database\Factories\AdRewardSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'token_hash',
    'status',
    'reward_amount',
    'ad_network_transaction_id',
    'wallet_transaction_id',
    'metadata',
    'expires_at',
    'credited_at',
])]
class AdRewardSession extends Model
{
    /** @use HasFactory<AdRewardSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AdRewardSessionStatus::class,
            'reward_amount' => 'integer',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'credited_at' => 'datetime',
        ];
    }

    public static function hashToken(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WalletTransaction, $this>
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
