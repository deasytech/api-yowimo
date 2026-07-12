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
            'sort_order' => 'integer',
        ];
    }
}
