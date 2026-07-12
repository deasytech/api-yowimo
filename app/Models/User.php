<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
])]
class User extends Authenticatable
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
        ];
    }
}
