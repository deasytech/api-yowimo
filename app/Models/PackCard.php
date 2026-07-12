<?php

namespace App\Models;

use App\Enums\PackCardKind;
use Database\Factories\PackCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pack_id',
    'kind',
    'text',
    'position',
    'is_preview',
])]
class PackCard extends Model
{
    /** @use HasFactory<PackCardFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => PackCardKind::class,
            'position' => 'integer',
            'is_preview' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }
}
