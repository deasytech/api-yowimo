<?php

use App\Models\CardReport;
use App\Models\PackCard;
use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function reportEndpoint(PackCard $card): string
{
    return "/api/v1/cards/{$card->id}/report";
}

it('rejects reporting a card with no bearer token', function () {
    $card = PackCard::factory()->create();

    $this->postJson(reportEndpoint($card))->assertStatus(401);
});

it('logs a report for a card', function () {
    $token = $this->clerkToken(['sub' => 'card_reporter']);
    $card = PackCard::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(reportEndpoint($card), ['reason' => 'offensive', 'note' => 'This card is not appropriate.'])
        ->assertStatus(201)
        ->assertJsonPath('data.reason', 'offensive')
        ->assertJsonPath('data.pack_card_id', $card->id);

    $user = User::where('clerk_user_id', 'card_reporter')->firstOrFail();
    expect(CardReport::where('pack_card_id', $card->id)->where('reporter_id', $user->id)->where('reason', 'offensive')->exists())->toBeTrue();
});

it('does not take any automatic action on the card', function () {
    $token = $this->clerkToken(['sub' => 'card_reporter_no_action']);
    $card = PackCard::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(reportEndpoint($card), ['reason' => 'spam'])
        ->assertStatus(201);

    expect($card->fresh())->not->toBeNull();
});

it('allows reporting the same card more than once', function () {
    $token = $this->clerkToken(['sub' => 'card_reporter_twice']);
    $card = PackCard::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(reportEndpoint($card), ['reason' => 'spam'])->assertStatus(201);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(reportEndpoint($card), ['reason' => 'other'])->assertStatus(201);

    expect(CardReport::where('pack_card_id', $card->id)->count())->toBe(2);
});

it('rejects an invalid reason', function () {
    $token = $this->clerkToken(['sub' => 'card_reporter_invalid_reason']);
    $card = PackCard::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(reportEndpoint($card), ['reason' => 'not-a-real-reason'])
        ->assertStatus(422);
});

it('rejects a missing reason', function () {
    $token = $this->clerkToken(['sub' => 'card_reporter_missing_reason']);
    $card = PackCard::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(reportEndpoint($card), [])
        ->assertStatus(422);
});

it('404s for a nonexistent card', function () {
    $token = $this->clerkToken(['sub' => 'card_reporter_missing_card']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/cards/999999/report', ['reason' => 'spam'])
        ->assertStatus(404);
});
