<?php

namespace App\Services\Purchase;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PaymentMethodService
{
    /**
     * @return Collection<int, PaymentMethod>
     */
    public function listFor(User $user): Collection
    {
        return $user->paymentMethods()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * The method to charge when a purchase doesn't specify one explicitly:
     * the customer's chosen default, or (if they never chose one) whichever
     * was saved most recently.
     */
    public function defaultFor(User $user): ?PaymentMethod
    {
        return $user->paymentMethods()->where('is_default', true)->first()
            ?? $user->paymentMethods()->latest()->first();
    }

    /**
     * Persists a reusable authorization returned by the payment provider as
     * a saved payment method (or refreshes one that already exists for it,
     * since card metadata like the expiry can change). The very first saved
     * method for a user becomes their default automatically.
     *
     * @param  array<string, mixed>  $authorization
     */
    public function saveFromAuthorization(User $user, string $provider, array $authorization): PaymentMethod
    {
        $method = PaymentMethod::query()->firstOrNew([
            'provider' => $provider,
            'authorization_code' => $authorization['authorization_code'],
        ]);

        $isNew = ! $method->exists;

        $method->fill([
            'user_id' => $user->id,
            'card_type' => $authorization['card_type'] ?? null,
            'last4' => $authorization['last4'] ?? null,
            'exp_month' => $authorization['exp_month'] ?? null,
            'exp_year' => $authorization['exp_year'] ?? null,
            'bank' => $authorization['bank'] ?? null,
        ]);

        if ($isNew) {
            $method->is_default = ! $user->paymentMethods()->exists();
        }

        $method->save();

        return $method;
    }

    public function setDefault(User $user, PaymentMethod $method): void
    {
        $user->paymentMethods()->where('id', '!=', $method->id)->update(['is_default' => false]);

        $method->update(['is_default' => true]);
    }

    /**
     * Deletes a saved payment method, promoting the next most recent one to
     * default if the one removed was the default.
     */
    public function delete(PaymentMethod $method): void
    {
        $wasDefault = $method->is_default;
        $userId = $method->user_id;

        $method->delete();

        if ($wasDefault) {
            PaymentMethod::query()->where('user_id', $userId)->latest()->first()?->update(['is_default' => true]);
        }
    }
}
