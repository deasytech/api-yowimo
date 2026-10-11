<?php

namespace App\Models;

use App\Enums\SponsorshipInviteStatus;
use App\Enums\SponsorshipScope;
use Database\Factories\SponsorshipInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'party_id',
    'scope',
    'amount',
    'status',
    'token',
    'sponsor_id',
    'paid_at',
    'expires_at',
])]
class SponsorshipInvite extends Model
{
    /** @use HasFactory<SponsorshipInviteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => SponsorshipScope::class,
            'amount' => 'integer',
            'status' => SponsorshipInviteStatus::class,
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
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
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    /**
     * The stored status never flips to 'expired' on its own — there's no
     * scheduled job for it — so an invite past its expires_at is still
     * 'pending' at rest and only reads as expired here, at the moment it's
     * checked (API responses, pay()'s own guard).
     */
    public function effectiveStatus(): SponsorshipInviteStatus
    {
        if ($this->status === SponsorshipInviteStatus::Pending && $this->expires_at->isPast()) {
            return SponsorshipInviteStatus::Expired;
        }

        return $this->status;
    }
}
