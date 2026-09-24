<?php

namespace App\Filament\Support;

use App\Enums\GameIntensity;
use App\Enums\PackCardKind;
use App\Enums\PackCategory;
use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Enums\UserStatus;
use App\Enums\WalletTransactionType;

/**
 * Centralizes badge colors for enum-backed columns/entries, one method per
 * enum, so a resource's list and view pages can't drift out of sync with
 * each other on how the same value is colored. Only Filament's built-in
 * semantic colors are used (primary/success/warning/danger/info/gray) —
 * these are the only ones registered without extra Panel configuration.
 */
class BadgeColors
{
    public static function gameIntensity(GameIntensity $state): string
    {
        return match ($state) {
            GameIntensity::Chill => 'success',
            GameIntensity::Medium => 'warning',
            GameIntensity::Wild => 'danger',
        };
    }

    public static function packCategory(PackCategory $state): string
    {
        return match ($state) {
            PackCategory::Spicy => 'danger',
            PackCategory::Couples => 'info',
            PackCategory::Family => 'success',
            PackCategory::Corporate => 'gray',
            PackCategory::Limited => 'warning',
        };
    }

    public static function packCardKind(PackCardKind $state): string
    {
        return match ($state) {
            PackCardKind::Truth => 'info',
            PackCardKind::Dare => 'danger',
        };
    }

    public static function partyMode(PartyMode $state): string
    {
        return match ($state) {
            PartyMode::Online => 'info',
            PartyMode::Hybrid => 'warning',
            PartyMode::InPerson => 'success',
        };
    }

    public static function partyVisibility(PartyVisibility $state): string
    {
        return match ($state) {
            PartyVisibility::Public => 'success',
            PartyVisibility::Private => 'gray',
        };
    }

    public static function partyStatus(PartyStatus $state): string
    {
        return match ($state) {
            PartyStatus::Draft => 'gray',
            PartyStatus::Scheduled => 'info',
            PartyStatus::Live => 'success',
            PartyStatus::Ended => 'gray',
            PartyStatus::Cancelled => 'danger',
        };
    }

    public static function userStatus(UserStatus $state): string
    {
        return match ($state) {
            UserStatus::Active => 'success',
            UserStatus::Deactivated => 'gray',
        };
    }

    public static function walletTransactionType(WalletTransactionType $state): string
    {
        return match ($state) {
            WalletTransactionType::TopUp,
            WalletTransactionType::Bonus,
            WalletTransactionType::Reward => 'success',
            WalletTransactionType::Purchase => 'info',
            WalletTransactionType::Refund => 'warning',
            WalletTransactionType::Adjustment => 'gray',
        };
    }
}
