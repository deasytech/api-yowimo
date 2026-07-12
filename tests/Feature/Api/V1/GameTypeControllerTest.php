<?php

use App\Models\GameType;
use Tests\Support\FakesClerk;

const API_V1_GAME_TYPES_ENDPOINT = '/api/v1/game-types';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_GAME_TYPES_ENDPOINT)->assertStatus(401);
});

it('lists active game types ordered by sort order', function () {
    $token = $this->clerkToken();

    GameType::factory()->create(['name' => 'Second', 'sort_order' => 2]);
    GameType::factory()->create(['name' => 'First', 'sort_order' => 1]);
    GameType::factory()->create(['name' => 'Hidden', 'sort_order' => 0, 'is_active' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_GAME_TYPES_ENDPOINT)
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $response->assertJsonPath('data.0.name', 'First');
    $response->assertJsonPath('data.1.name', 'Second');
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta'))->toHaveKeys(['per_page', 'has_more_pages', 'next_cursor', 'prev_cursor']);
});

it('searches game types by name', function () {
    $token = $this->clerkToken();

    GameType::factory()->create(['name' => 'Truth or Dare', 'tagline' => 'Spill it']);
    GameType::factory()->create(['name' => 'Charades', 'tagline' => 'Act it out']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_GAME_TYPES_ENDPOINT.'?search=Charades')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Charades');
});

it('paginates game types with a cursor', function () {
    $token = $this->clerkToken();

    GameType::factory()->count(3)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();

    $first = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_GAME_TYPES_ENDPOINT.'?per_page=2')
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');

    $nextCursor = $first->json('meta.next_cursor');
    expect($nextCursor)->not->toBeNull();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_GAME_TYPES_ENDPOINT."?per_page=2&cursor={$nextCursor}")
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});
