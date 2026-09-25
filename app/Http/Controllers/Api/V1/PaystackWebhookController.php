<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\InvalidPaystackWebhookException;
use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\Paystack\PaystackClient;
use App\Support\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Records every signature-verified Paystack event as an audit trail,
 * idempotently on a composite key of the event type and the resource id —
 * not the resource id alone, since Paystack can send more than one event
 * type for the same underlying transaction (e.g. charge.success and a later
 * refund event share the transaction id); keying on the id alone would make
 * the second delivery collide with the first and get silently dropped as a
 * "duplicate".
 *
 * Crediting the wallet itself does not happen here: the purchase endpoint
 * already verifies a charge server-to-server against Paystack directly
 * (PaystackPaymentProvider::charge()) before crediting anything, so this
 * webhook is a confirmation/audit log, not the primary path. Known gap: if
 * the client never calls back after a real charge succeeds (e.g. the app
 * is killed mid-flow), the charge is never reconciled — closing that gap
 * needs a payment-intent record created before the charge, which this pass
 * doesn't add.
 */
class PaystackWebhookController extends Controller
{
    public function __construct(private readonly PaystackClient $client) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->client->verifyWebhookSignature($request->getContent(), $request->header('x-paystack-signature'))) {
            throw new InvalidPaystackWebhookException;
        }

        $payload = $request->json()->all();
        $transactionId = Arr::get($payload, 'data.id');
        $eventType = Arr::get($payload, 'event', '');

        if ($transactionId !== null) {
            $this->recordOnce("paystack_{$eventType}_{$transactionId}", $eventType, $payload);
        }

        return ApiResponse::success(message: 'Webhook processed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordOnce(string $eventId, string $eventType, array $payload): void
    {
        try {
            WebhookEvent::query()->create([
                'provider' => 'paystack',
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'processed_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (! (in_array($e->getCode(), ['23000', '23505'], true) && str_contains($e->getMessage(), 'event_id'))) {
                throw $e;
            }

            // Another delivery of the same event won the race; already recorded.
        }
    }
}
