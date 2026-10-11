<?php

namespace App\Models;

use App\Enums\PartyMemberStatus;
use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Enums\SponsorshipInviteStatus;
use App\Enums\SponsorshipScope;
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
    'entry_fee',
    'sponsorship_scope',
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
            'entry_fee' => 'integer',
            'sponsorship_scope' => SponsorshipScope::class,
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

    /**
     * All membership rows ever created for this party, current and past —
     * the full history. See activeMembers() for the current roster.
     *
     * @return HasMany<PartyMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(PartyMember::class);
    }

    /**
     * @return HasMany<PartyMember, $this>
     */
    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', PartyMemberStatus::Active);
    }

    /**
     * @return HasMany<GameSession, $this>
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * @return HasMany<SponsorshipInvite, $this>
     */
    public function sponsorshipInvites(): HasMany
    {
        return $this->hasMany(SponsorshipInvite::class);
    }

    /**
     * The invite that currently represents this party's sponsorship state
     * for API responses: the paid one if there is one, otherwise the most
     * recently created (e.g. still pending, or expired/cancelled with no
     * successor yet). Null if sponsorship was never requested or no invite
     * has been created yet. Works off the loaded relation when available so
     * callers can eager-load sponsorshipInvites to avoid N+1 across a list.
     */
    public function currentSponsorshipInvite(): ?SponsorshipInvite
    {
        $invites = $this->relationLoaded('sponsorshipInvites')
            ? $this->sponsorshipInvites
            : $this->sponsorshipInvites()->get();

        return $invites->first(fn (SponsorshipInvite $invite) => $invite->status === SponsorshipInviteStatus::Paid)
            ?? $invites->sortByDesc('created_at')->first();
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

    public function isMemberOf(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (array_key_exists('viewer_is_member', $this->attributes)) {
            return (bool) $this->attributes['viewer_is_member'];
        }

        return $this->relationLoaded('activeMembers')
            ? $this->activeMembers->contains('user_id', $user->id)
            : $this->activeMembers()->where('user_id', $user->id)->exists();
    }
}
