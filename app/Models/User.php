<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'clerk_user_id',
    'username',
    'email',
    'first_name',
    'last_name',
    'display_name',
    'avatar_url',
    'bio',
    'date_of_birth',
    'country_code',
    'interests',
    'privacy_settings',
    'status',
    'password',
    'is_admin',
    'xp',
])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'privacy_settings' => 'array',
            'date_of_birth' => 'date',
            'last_seen_at' => 'datetime',
            'status' => UserStatus::class,
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'xp' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function getFilamentName(): string
    {
        return $this->display_name ?? $this->username ?? $this->email ?? "User #{$this->id}";
    }

    /**
     * @return HasMany<Party, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(Party::class, 'host_id');
    }

    /**
     * @return HasMany<PartyLike, $this>
     */
    public function partyLikes(): HasMany
    {
        return $this->hasMany(PartyLike::class);
    }

    /**
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return HasOne<PushToken, $this>
     */
    public function pushToken(): HasOne
    {
        return $this->hasOne(PushToken::class);
    }

    /**
     * @return HasMany<Friendship, $this>
     */
    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    /**
     * @return HasMany<Friendship, $this>
     */
    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'receiver_id');
    }

    /**
     * Overrides the `Notifiable` trait's built-in `notifications()` (which targets
     * Laravel's default polymorphic database-notifications schema) — this app's
     * `notifications` table uses its own schema (see `Notification` model) and is
     * only ever written to via the custom `InAppChannel`, not the `database` channel.
     *
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * @return HasMany<XpTransaction, $this>
     */
    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
    }

    /**
     * @return HasMany<UserBadge, $this>
     */
    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
