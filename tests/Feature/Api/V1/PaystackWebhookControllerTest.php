<?php

use App\Models\WebhookEvent;

const API_V1_PAYSTACK_WEBHOOK_ENDPOINT = '/api/v1/webhooks/paystack';

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_fake']);
});

/**
 * postJson() sends json_encode($payload) as the raw body, so signing that
 * same encoding here produces the exact signature the controller verifies
 * against $request->getContent().
 */
function paystackWebhookSignature(array $payload): string
{
    return hash_hmac('sha512', json_encode($payload), config('services.paystack.secret_key'));
}

it('rejects a webhook with no signature header', function () {
    $this->postJson(API_V1_PAYSTACK_WEBHOOK_ENDPOINT, ['event' => 'charge.success', 'data' => ['id' => 1]])
        ->assertStatus(400)
        ->assertJson(['success' => false]);
});

it('rejects a webhook with an invalid signature', function () {
    $this->withHeader('x-paystack-signature', 'not-the-real-signature')
        ->postJson(API_V1_PAYSTACK_WEBHOOK_ENDPOINT, ['event' => 'charge.success', 'data' => ['id' => 1]])
        ->assertStatus(400);
});

it('records a signature-verified event', function () {
    $payload = ['event' => 'charge.success', 'data' => ['id' => 555555, 'reference' => 'ref_1']];

    $this->withHeader('x-paystack-signature', paystackWebhookSignature($payload))
        ->postJson(API_V1_PAYSTACK_WEBHOOK_ENDPOINT, $payload)
        ->assertStatus(200);

    $event = WebhookEvent::where('event_id', 'paystack_charge.success_555555')->first();
    expect($event)->not->toBeNull();
    expect($event->provider)->toBe('paystack');
    expect($event->event_type)->toBe('charge.success');
});

it('does not double-record a duplicate webhook delivery', function () {
    $payload = ['event' => 'charge.success', 'data' => ['id' => 777777]];
    $signature = paystackWebhookSignature($payload);

    $this->withHeader('x-paystack-signature', $signature)->postJson(API_V1_PAYSTACK_WEBHOOK_ENDPOINT, $payload)->assertStatus(200);
    $this->withHeader('x-paystack-signature', $signature)->postJson(API_V1_PAYSTACK_WEBHOOK_ENDPOINT, $payload)->assertStatus(200);

    expect(WebhookEvent::where('event_id', 'paystack_charge.success_777777')->count())->toBe(1);
});

it('records a second event of a different type on the same resource id, not deduped', function () {
    // Different event types can legitimately share a resource id (e.g. a
    // charge and a later refund on the same transaction) — the dedup key
    // must not collapse them into one.
    $success = ['event' => 'charge.success', 'data' => ['id' => 888888]];
    $refund = ['event' => 'refund.processed', 'data' => ['id' => 888888]];

    $this->withHeader('x-paystack-signature', paystackWebhookSignature($success))
        ->postJson(API_V1_PAYSTACK_WEBHOOK_ENDPOINT, $success)
        ->assertStatus(200);

    $this->withHeader('x-paystack-signature', paystackWebhookSignature($refund))
        ->postJson(API_V1_PAYSTACK_WEBHOOK_ENDPOINT, $refund)
        ->assertStatus(200);

    expect(WebhookEvent::where('event_id', 'paystack_charge.success_888888')->exists())->toBeTrue();
    expect(WebhookEvent::where('event_id', 'paystack_refund.processed_888888')->exists())->toBeTrue();
});
