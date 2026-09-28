<?php

namespace App\Models;

use App\Enums\CardReportReason;
use Database\Factories\CardReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pack_card_id',
    'reporter_id',
    'reason',
    'note',
])]
class CardReport extends Model
{
    /** @use HasFactory<CardReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => CardReportReason::class,
        ];
    }

    /**
     * @return BelongsTo<PackCard, $this>
     */
    public function packCard(): BelongsTo
    {
        return $this->belongsTo(PackCard::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
