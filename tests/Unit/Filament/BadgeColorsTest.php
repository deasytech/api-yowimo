<?php

use App\Enums\GameSessionStatus;
use App\Enums\PartyStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Support\BadgeColors;

it('has a badge color for every game session status', function (GameSessionStatus $status) {
    expect(BadgeColors::gameSessionStatus($status))->toBeString()->not->toBeEmpty();
})->with(GameSessionStatus::cases());

it('has a badge color for every party status', function (PartyStatus $status) {
    expect(BadgeColors::partyStatus($status))->toBeString()->not->toBeEmpty();
})->with(PartyStatus::cases());

it('has a badge color for every wallet transaction type', function (WalletTransactionType $type) {
    expect(BadgeColors::walletTransactionType($type))->toBeString()->not->toBeEmpty();
})->with(WalletTransactionType::cases());
