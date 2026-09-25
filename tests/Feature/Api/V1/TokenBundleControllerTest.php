<?php

use App\Models\TokenBundle;
use Tests\Support\FakesClerk;

const API_V1_TOKEN_BUNDLES_ENDPOINT = '/api/v1/token-bundles';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_TOKEN_BUNDLES_ENDPOINT)->assertStatus(401);
});

it('lists active token bundles ordered by sort order', function () {
    $token = $this->clerkToken();

    TokenBundle::factory()->create(['name' => 'Second', 'sort_order' => 2]);
    TokenBundle::factory()->create(['name' => 'First', 'sort_order' => 1]);
    TokenBundle::factory()->create(['name' => 'Inactive', 'is_active' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_TOKEN_BUNDLES_ENDPOINT)
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(2);
    $response->assertJsonPath('data.0.name', 'First');
    $response->assertJsonPath('data.1.name', 'Second');
});

it('shows the default (Naira) price to a buyer with a Nigerian profile', function () {
    $token = $this->clerkToken(['sub' => 'user_bundle_nigeria', 'email' => 'lagos-buyer@example.com']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/users/me', ['country_code' => 'NG'])
        ->assertOk();

    TokenBundle::factory()->create(['name' => 'Starter', 'price' => 3000, 'currency' => 'NGN', 'price_usd' => 1.99]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_TOKEN_BUNDLES_ENDPOINT)
        ->assertStatus(200);

    $response->assertJsonPath('data.0.price', 3000);
    $response->assertJsonPath('data.0.currency', 'NGN');
});

it('shows the default (Naira) price to a buyer with no country on file', function () {
    $token = $this->clerkToken(['sub' => 'user_bundle_no_country']);

    TokenBundle::factory()->create(['name' => 'Starter', 'price' => 3000, 'currency' => 'NGN', 'price_usd' => 1.99]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_TOKEN_BUNDLES_ENDPOINT)
        ->assertStatus(200);

    $response->assertJsonPath('data.0.price', 3000);
    $response->assertJsonPath('data.0.currency', 'NGN');
});

it('shows the USD price to a buyer confirmed to be outside Nigeria', function () {
    $token = $this->clerkToken(['sub' => 'user_bundle_other_country', 'email' => 'ny-buyer@example.com']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/users/me', ['country_code' => 'US'])
        ->assertOk();

    TokenBundle::factory()->create(['name' => 'Starter', 'price' => 3000, 'currency' => 'NGN', 'price_usd' => 1.99]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_TOKEN_BUNDLES_ENDPOINT)
        ->assertStatus(200);

    $response->assertJsonPath('data.0.price', 1.99);
    $response->assertJsonPath('data.0.currency', 'USD');
});

it('falls back to the default price for a non-Nigerian buyer when no USD price is set', function () {
    $token = $this->clerkToken(['sub' => 'user_bundle_no_usd_price', 'email' => 'ny-buyer2@example.com']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/users/me', ['country_code' => 'US'])
        ->assertOk();

    TokenBundle::factory()->create(['name' => 'Starter', 'price' => 3000, 'currency' => 'NGN', 'price_usd' => null]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_TOKEN_BUNDLES_ENDPOINT)
        ->assertStatus(200);

    $response->assertJsonPath('data.0.price', 3000);
    $response->assertJsonPath('data.0.currency', 'NGN');
});
