<?php

use App\Enums\PackCategory;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\PackPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

it('flags owned_by_me per pack on the list endpoint', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_list_owner']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $viewer = User::where('clerk_user_id', 'user_pack_list_owner')->firstOrFail();

    $owned = Pack::factory()->create(['name' => 'Owned Pack']);
    $notOwned = Pack::factory()->create(['name' => 'Unowned Pack']);
    PackPurchase::factory()->create(['pack_id' => $owned->id, 'user_id' => $viewer->id]);
    // A purchase by a different user must not mark this pack as owned by the viewer.
    PackPurchase::factory()->create(['pack_id' => $notOwned->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT)
        ->assertStatus(200);

    $byName = collect($response->json('data'))->keyBy('name');

    expect($byName['Owned Pack']['owned_by_me'])->toBeTrue()
        ->and($byName['Unowned Pack']['owned_by_me'])->toBeFalse();
});

it('flags owned_by_me per pack on the featured endpoint', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_featured_owner']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $viewer = User::where('clerk_user_id', 'user_pack_featured_owner')->firstOrFail();

    $owned = Pack::factory()->create(['name' => 'Owned Featured Pack', 'is_featured' => true]);
    PackPurchase::factory()->create(['pack_id' => $owned->id, 'user_id' => $viewer->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT.'/featured')
        ->assertStatus(200)
        ->assertJsonPath('data.0.owned_by_me', true);
});

it('resolves owned_by_me for a full page of packs in a single extra query', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_query_count']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $viewer = User::where('clerk_user_id', 'user_pack_query_count')->firstOrFail();

    $packs = Pack::factory()->count(10)->create();
    PackPurchase::factory()->create(['pack_id' => $packs->first()->id, 'user_id' => $viewer->id]);

    DB::enableQueryLog();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PACKS_ENDPOINT.'?per_page=10')
        ->assertStatus(200);

    $packPurchaseQueries = collect(DB::getQueryLog())
        ->filter(fn ($query) => str_contains($query['query'], 'pack_purchases'));

    DB::disableQueryLog();

    expect($packPurchaseQueries)->toHaveCount(1);
});
