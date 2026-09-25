<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseTokenBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:255'],
            // Both optional, and mutually exclusive in practice: a
            // payment_reference (from a charge the client already completed
            // via the provider's own SDK) takes priority in PurchaseService
            // over payment_method_id (a previously saved card); with
            // neither, the user's default saved method is used if they have
            // one. See PaymentProvider::charge().
            'payment_method_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('payment_methods', 'id')->where('user_id', $this->user()->id),
            ],
            'payment_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
