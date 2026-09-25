<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'provider',
    'authorization_code',
    'card_type',
    'last4',
    'exp_month',
    'exp_year',
    'bank',
    'is_default',
])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * The sensitive token Paystack uses to actually charge the card — never
     * serialized, even though PaymentMethodResource already omits it
     * explicitly. Direct attribute access (->authorization_code) is
     * unaffected; this only guards toArray()/toJson().
     *
     * @var array<int, string>
     */
    protected $hidden = ['authorization_code'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
