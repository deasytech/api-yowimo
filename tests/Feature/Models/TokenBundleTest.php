<?php

use App\Models\TokenBundle;
use App\Models\User;

it('charges the default (Naira) price to a Nigerian buyer', function () {
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => 1.99]);
    $buyer = User::factory()->create(['country_code' => 'NG']);

    expect($bundle->priceFor($buyer))->toBe(['amount' => 3000.0, 'currency' => 'NGN']);
});

it('charges the default price when the buyer has no country on file', function () {
    // The company is Nigerian: the default price is the one charged unless
    // a buyer's country is *confirmed* to be somewhere else.
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => 1.99]);
    $buyer = User::factory()->create(['country_code' => null]);

    expect($bundle->priceFor($buyer))->toBe(['amount' => 3000.0, 'currency' => 'NGN']);
});

it('charges the default price when there is no buyer at all', function () {
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => 1.99]);

    expect($bundle->priceFor(null))->toBe(['amount' => 3000.0, 'currency' => 'NGN']);
});

it('charges the USD price to a buyer confirmed to be outside Nigeria', function () {
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => 1.99]);
    $buyer = User::factory()->create(['country_code' => 'US']);

    expect($bundle->priceFor($buyer))->toBe(['amount' => 1.99, 'currency' => 'USD']);
});

it('is case-insensitive about the country code', function () {
    $bundle = TokenBundle::factory()->create(['price_usd' => 1.99]);
    $buyer = User::factory()->create(['country_code' => 'us']);

    expect($bundle->priceFor($buyer)['currency'])->toBe('USD');
});

it('falls back to the default price for a non-Nigerian buyer when no USD price is set', function () {
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => null]);
    $buyer = User::factory()->create(['country_code' => 'US']);

    expect($bundle->priceFor($buyer))->toBe(['amount' => 3000.0, 'currency' => 'NGN']);
});
