<?php

namespace App\Filament\Support;

use App\Enums\BadgeKey;
use App\Enums\FriendshipStatus;
use App\Enums\GameIntensity;
use App\Enums\GameSessionStatus;
use App\Enums\PackCardKind;
use App\Enums\PackCategory;
use App\Enums\PartyMemberStatus;
use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Enums\PushPlatform;
use App\Enums\UserStatus;
use App\Enums\VoteCategory;
use App\Enums\WalletTransactionType;
use App\Enums\XpTransactionType;

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

    public static function partyMemberStatus(PartyMemberStatus $state): string
    {
        return match ($state) {
            PartyMemberStatus::Active => 'success',
            PartyMemberStatus::Left => 'gray',
            PartyMemberStatus::Removed => 'danger',
        };
    }

    public static function friendshipStatus(FriendshipStatus $state): string
    {
        return match ($state) {
            FriendshipStatus::Pending => 'warning',
            FriendshipStatus::Accepted => 'success',
            FriendshipStatus::Rejected => 'danger',
            FriendshipStatus::Cancelled,
            FriendshipStatus::Removed => 'gray',
        };
    }

    public static function gameSessionStatus(GameSessionStatus $state): string
    {
        return match ($state) {
            GameSessionStatus::Running => 'info',
            GameSessionStatus::Completed => 'success',
        };
    }

    public static function voteCategory(VoteCategory $state): string
    {
        return match ($state) {
            VoteCategory::Winner => 'warning',
            VoteCategory::Funny => 'info',
            VoteCategory::Creativity => 'primary',
        };
    }

    public static function xpTransactionType(XpTransactionType $state): string
    {
        return match ($state) {
            XpTransactionType::TurnWinnerVote,
            XpTransactionType::TurnFunnyVote,
            XpTransactionType::TurnCreativityVote => 'info',
            XpTransactionType::ChallengeCompleted => 'success',
            XpTransactionType::MvpBonus => 'warning',
        };
    }

    public static function pushPlatform(PushPlatform $state): string
    {
        return match ($state) {
            PushPlatform::Ios => 'info',
            PushPlatform::Android => 'success',
        };
    }

    public static function badgeKey(BadgeKey $state): string
    {
        return match ($state) {
            BadgeKey::FirstParty,
            BadgeKey::SocialButterfly => 'info',
            BadgeKey::HundredParties,
            BadgeKey::PartyKing => 'warning',
            BadgeKey::PerfectGame,
            BadgeKey::TruthMaster,
            BadgeKey::DareDevil => 'primary',
        };
    }
}
