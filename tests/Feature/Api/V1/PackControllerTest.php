<?php

use App\Enums\PackCategory;
use App\Models\Pack;
use App\Models\PackCard;
use Tests\Support\FakesClerk;

const API_V1_PACKS_ENDPOINT = '/api/v1/packs';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_PACKS_ENDPOINT)->assertStatus(401);
});

it('lists active packs', function () {
    $token = $this->clerkToken();

    Pack::factory()->create(['name' => 'Visible Pack']);
    Pack::factory()->create(['name' => 'Hidden Pack', 'is_active' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT)
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.name', 'Visible Pack');
});

it('filters packs by category', function () {
    $token = $this->clerkToken();

    Pack::factory()->create(['name' => 'Spicy One', 'category' => PackCategory::Spicy]);
    Pack::factory()->create(['name' => 'Family One', 'category' => PackCategory::Family]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT.'?category=spicy')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Spicy One');
});

it('searches packs by name', function () {
    $token = $this->clerkToken();

    Pack::factory()->create(['name' => 'Neon Confessions']);
    Pack::factory()->create(['name' => 'Office Icebreakers']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT.'?search=Neon')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Neon Confessions');
});

it('returns only featured packs on the featured endpoint', function () {
    $token = $this->clerkToken();

    Pack::factory()->create(['name' => 'Featured Pack', 'is_featured' => true]);
    Pack::factory()->create(['name' => 'Regular Pack', 'is_featured' => false]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT.'/featured')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Featured Pack');
});

it('shows a pack with its preview cards', function () {
    $token = $this->clerkToken();

    $pack = Pack::factory()->create(['name' => 'Detail Pack']);
    PackCard::factory()->preview()->create(['pack_id' => $pack->id, 'text' => 'Preview truth?']);
    PackCard::factory()->create(['pack_id' => $pack->id, 'is_preview' => false, 'text' => 'Not a preview']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT."/{$pack->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'Detail Pack');

    expect($response->json('data.preview_cards'))->toHaveCount(1);
    $response->assertJsonPath('data.preview_cards.0.text', 'Preview truth?');
});

it('returns 404 for an inactive pack', function () {
    $token = $this->clerkToken();

    $pack = Pack::factory()->create(['is_active' => false]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT."/{$pack->id}")
        ->assertStatus(404);
});
