<?php

use App\Enums\PartyStatus;
use App\Enums\XpTransactionType;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakesClerk;

const API_V1_ME_ENDPOINT = '/api/v1/users/me';
const API_V1_ME_STATS_ENDPOINT = '/api/v1/users/me/stats';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});

it('rejects requests with an invalid bearer token', function () {
    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(401)
        ->assertJson(['success' => false]);
});

it('just-in-time provisions a new internal user from a verified clerk token', function () {
    $token = $this->clerkToken([
        'sub' => 'user_abc123',
        'email' => 'player@yowimo.app',
        'name' => 'Player One',
    ]);

    expect(User::query()->count())->toBe(0);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
        ]);

    $response->assertJsonPath('data.email', 'player@yowimo.app');
    $response->assertJsonPath('data.display_name', 'Player One');
    $response->assertJsonPath('data.status', 'active');
    $response->assertJsonPath('data.wallet.balance', 0);
    $response->assertJsonPath('data.wallet.currency', 'tokens');

    expect(User::query()->count())->toBe(1);
    expect(User::query()->first()->clerk_user_id)->toBe('user_abc123');
});

it('resolves the same internal user on subsequent requests for the same clerk id', function () {
    $token = $this->clerkToken(['sub' => 'user_same']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();

    expect(User::query()->count())->toBe(1);
});

it('rejects a valid token belonging to a deactivated user', function () {
    $token = $this->clerkToken(['sub' => 'user_gone']);

    User::factory()->deactivated()->create(['clerk_user_id' => 'user_gone']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(401);
});

it('updates the authenticated users profile', function () {
    $token = $this->clerkToken(['sub' => 'user_update']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_ME_ENDPOINT, [
            'username' => 'partyhost99',
            'bio' => 'Here for the games.',
            'country_code' => 'GH',
            'interests' => ['music', 'trivia'],
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.username', 'partyhost99')
        ->assertJsonPath('data.bio', 'Here for the games.')
        ->assertJsonPath('data.country_code', 'GH')
        ->assertJsonPath('data.interests', ['music', 'trivia']);

    expect(User::where('clerk_user_id', 'user_update')->first()->username)->toBe('partyhost99');
});

it('rejects profile updates that fail validation with the standard error envelope', function () {
    $token = $this->clerkToken(['sub' => 'user_invalid']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_ME_ENDPOINT, ['username' => 'a'])
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed',
        ])
        ->assertJsonValidationErrors('username');
});

it('does not allow taking a username already used by another user', function () {
    User::factory()->create(['username' => 'taken']);
    $token = $this->clerkToken(['sub' => 'user_conflict']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_ME_ENDPOINT, ['username' => 'taken'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('username');
});

// Avatar file uploads must be sent as POST with a `_method=PATCH` field, not
// as a real wire-level PATCH: this project's declared runtime is PHP ^8.3
// (composer.json/CI both pin 8.3), and on PHP <8.4 the framework only parses
// a multipart/form-data body for a real POST request — a genuine PATCH with
// a multipart body arrives with an empty $_POST/$_FILES, silently dropping
// every field, not just the avatar. Laravel's method-override support (on
// by default, `Illuminate\Foundation\Http\Kernel::handle()`) then routes the
// POST to this same PATCH route/controller. Using `->patch(...)` directly in
// a test would still pass — Laravel's test client injects files straight
// into the request, bypassing real body parsing entirely — which is exactly
// why that shape doesn't catch this; POST + `_method` is used below so the
// tests exercise the same contract a real client must use.
function patchWithMultipart(string $token, array $data)
{
    // The `clerk` guard caches its resolved user for the lifetime of the app
    // instance (see provisionVoteTestUser() in TurnVoteControllerTest), so it
    // must be forced to re-resolve before every call — otherwise a second
    // call with a different token would silently reuse the first user.
    app('auth')->forgetGuards();

    return test()->withHeader('Authorization', "Bearer {$token}")
        ->post(API_V1_ME_ENDPOINT, ['_method' => 'PATCH', ...$data]);
}

it('uploads an avatar and replaces the previous one', function () {
    Storage::fake('public');
    $token = $this->clerkToken(['sub' => 'user_avatar']);

    $firstAvatarUrl = patchWithMultipart($token, ['avatar' => UploadedFile::fake()->image('avatar.jpg')])
        ->assertStatus(200)
        ->json('data.avatar_url');

    expect($firstAvatarUrl)->not->toBeNull();
    Storage::disk('public')->assertExists(str($firstAvatarUrl)->after('/storage/')->toString());

    $secondAvatarUrl = patchWithMultipart($token, ['avatar' => UploadedFile::fake()->image('avatar-2.jpg')])
        ->assertStatus(200)
        ->json('data.avatar_url');

    expect($secondAvatarUrl)->not->toBe($firstAvatarUrl);
    Storage::disk('public')->assertExists(str($secondAvatarUrl)->after('/storage/')->toString());
    Storage::disk('public')->assertMissing(str($firstAvatarUrl)->after('/storage/')->toString());

    // The API response resolves a full URL; the stored column holds just
    // the disk-relative path (see StoredImageUrl) so it survives an
    // APP_URL/tunnel change instead of baking in whatever's active now.
    $storedAvatarUrl = User::where('clerk_user_id', 'user_avatar')->first()->avatar_url;
    expect($storedAvatarUrl)->not->toContain('://');
    expect($storedAvatarUrl)->toBe(str($secondAvatarUrl)->after('/storage/')->toString());
});

it('rejects a non-image file as the avatar', function () {
    Storage::fake('public');
    $token = $this->clerkToken(['sub' => 'user_bad_avatar']);

    patchWithMultipart($token, ['avatar' => UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml')])
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar');
});

it('never attempts to delete an external avatar_url when a new avatar is uploaded', function () {
    Storage::fake('public');
    $token = $this->clerkToken(['sub' => 'user_external_avatar']);

    // JIT-provision the user first, then seed an external avatar URL directly.
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $user = User::where('clerk_user_id', 'user_external_avatar')->firstOrFail();
    $user->update(['avatar_url' => 'https://img.clerk.com/some-external-avatar.png']);

    $uploadedAvatarUrl = patchWithMultipart($token, ['avatar' => UploadedFile::fake()->image('avatar.jpg')])
        ->assertStatus(200)
        ->json('data.avatar_url');

    expect($uploadedAvatarUrl)->not->toBe('https://img.clerk.com/some-external-avatar.png');
    Storage::disk('public')->assertExists(str($uploadedAvatarUrl)->after('/storage/')->toString());
});

it('never deletes another users real avatar file even if avatar_url is spoofed to point at it', function () {
    Storage::fake('public');

    $victimToken = $this->clerkToken(['sub' => 'user_victim']);
    $victimAvatarUrl = patchWithMultipart($victimToken, ['avatar' => UploadedFile::fake()->image('victim.jpg')])
        ->assertStatus(200)
        ->json('data.avatar_url');

    $attackerToken = $this->clerkToken(['sub' => 'user_attacker']);

    // Storage::fake() returns a scheme-relative path ("/storage/...") since
    // no APP_URL is resolved in tests; in production Storage::disk('public')
    // ->url() is already absolute. Prefix a fake origin so this exercises
    // the `url` validation rule the same way a real absolute avatar_url would.
    $victimAbsoluteAvatarUrl = 'http://localhost'.$victimAvatarUrl;

    // The attacker points their own avatar_url at the victim's real,
    // locally-stored avatar file — a plain URL field, no ownership check.
    // forgetGuards() forces the `clerk` guard to re-resolve for this new
    // token instead of reusing the victim it already cached (see
    // patchWithMultipart() above).
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$attackerToken}")
        ->patchJson(API_V1_ME_ENDPOINT, ['avatar_url' => $victimAbsoluteAvatarUrl])
        ->assertStatus(200)
        ->assertJsonPath('data.avatar_url', $victimAbsoluteAvatarUrl);

    // Uploading a new avatar must not delete the victim's file just because
    // it was sitting in the attacker's own avatar_url column.
    patchWithMultipart($attackerToken, ['avatar' => UploadedFile::fake()->image('attacker-new.jpg')])
        ->assertStatus(200);

    Storage::disk('public')->assertExists(str($victimAvatarUrl)->after('/storage/')->toString());
});

it('rejects profile stats requests with no bearer token', function () {
    $this->getJson(API_V1_ME_STATS_ENDPOINT)->assertStatus(401);
});

it('returns zeroed stats for a user with no history', function () {
    $token = $this->clerkToken(['sub' => 'user_stats_none']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_ME_STATS_ENDPOINT)
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profile stats retrieved successfully.',
            'data' => ['parties_count' => 0, 'mvp_count' => 0],
        ]);
});

it('counts only ended parties toward parties_count, whether hosted or joined', function () {
    $token = $this->clerkToken(['sub' => 'user_stats_parties']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $user = User::where('clerk_user_id', 'user_stats_parties')->firstOrFail();

    // Hosted and ended — counts.
    $hostedEnded = Party::factory()->create(['host_id' => $user->id, 'status' => PartyStatus::Ended]);
    PartyMember::factory()->create(['party_id' => $hostedEnded->id, 'user_id' => $user->id]);

    // Joined (not hosted) and ended — counts.
    $joinedEnded = Party::factory()->create(['status' => PartyStatus::Ended]);
    PartyMember::factory()->create(['party_id' => $joinedEnded->id, 'user_id' => $user->id]);

    // Hosted but still a draft — doesn't count.
    $draft = Party::factory()->create(['host_id' => $user->id, 'status' => PartyStatus::Draft]);
    PartyMember::factory()->create(['party_id' => $draft->id, 'user_id' => $user->id]);

    // Joined but the party was cancelled before it happened — doesn't count.
    $cancelled = Party::factory()->create(['status' => PartyStatus::Cancelled]);
    PartyMember::factory()->create(['party_id' => $cancelled->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_ME_STATS_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.parties_count', 2);
});

it('counts mvp_bonus xp transactions toward mvp_count, ignoring other xp types', function () {
    $token = $this->clerkToken(['sub' => 'user_stats_mvp']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $user = User::where('clerk_user_id', 'user_stats_mvp')->firstOrFail();

    XpTransaction::factory()->create(['user_id' => $user->id, 'type' => XpTransactionType::MvpBonus]);
    XpTransaction::factory()->create(['user_id' => $user->id, 'type' => XpTransactionType::MvpBonus]);
    XpTransaction::factory()->create(['user_id' => $user->id, 'type' => XpTransactionType::ChallengeCompleted]);
    XpTransaction::factory()->create(['type' => XpTransactionType::MvpBonus]); // another user's MVP bonus

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_ME_STATS_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.mvp_count', 2);
});
