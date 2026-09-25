<?php

namespace App\Models;

use Database\Factories\TokenBundleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slug',
    'name',
    'tokens',
    'price',
    'currency',
    'price_usd',
    'badge',
    'gradient',
    'is_active',
    'is_featured',
    'sort_order',
])]
class TokenBundle extends Model
{
    /** @use HasFactory<TokenBundleFactory> */
    use HasFactory;

    /**
     * The currency charged to a buyer whose profile is confirmed to be
     * outside Nigeria, when this bundle has a price_usd set.
     */
    public const USD = 'USD';

    public const NIGERIA_COUNTRY_CODE = 'NG';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gradient' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'tokens' => 'integer',
            'price' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The price and currency to charge $user. This is a Nigerian company:
     * price/currency (NGN, in practice) is the default charged to everyone,
     * *including* a buyer with no country on file — price_usd/USD is the
     * exception, used only once a buyer's profile confirms a non-Nigerian
     * country and this bundle has been priced in USD. A non-Nigerian buyer
     * still gets the default price when no USD price is set yet, so a
     * bundle is never unpurchasable while an admin catches up on pricing it
     * in USD.
     *
     * This is per-market pricing set by an admin, not a live currency
     * conversion — the two prices are independent, not derived from each
     * other by an exchange rate.
     *
     * @return array{amount: float, currency: string}
     */
    public function priceFor(?User $user): array
    {
        if ($this->isConfirmedNonNigerian($user) && $this->price_usd !== null) {
            return ['amount' => (float) $this->price_usd, 'currency' => self::USD];
        }

        return ['amount' => (float) $this->price, 'currency' => $this->currency];
    }

    private function isConfirmedNonNigerian(?User $user): bool
    {
        return $user
            && $user->country_code !== null
            && strtoupper($user->country_code) !== self::NIGERIA_COUNTRY_CODE;
    }
}
