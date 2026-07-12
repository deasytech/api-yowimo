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
