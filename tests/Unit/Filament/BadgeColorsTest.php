<?php

use App\Enums\GameSessionStatus;
use App\Filament\Support\BadgeColors;

it('has a badge color for every game session status', function (GameSessionStatus $status) {
    expect(BadgeColors::gameSessionStatus($status))->toBeString()->not->toBeEmpty();
})->with(GameSessionStatus::cases());
