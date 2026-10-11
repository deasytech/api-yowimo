<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\SponsorshipScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSponsorshipInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::enum(SponsorshipScope::class)],
        ];
    }
}
