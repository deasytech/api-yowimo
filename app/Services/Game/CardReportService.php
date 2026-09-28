<?php

namespace App\Services\Game;

use App\Enums\CardReportReason;
use App\Models\CardReport;
use App\Models\PackCard;
use App\Models\User;

class CardReportService
{
    /**
     * Logs a report for admin review. No automatic action is taken on the
     * card — moderation is a manual Filament review step for now.
     */
    public function report(User $reporter, PackCard $card, CardReportReason $reason, ?string $note): CardReport
    {
        return CardReport::create([
            'pack_card_id' => $card->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'note' => $note,
        ]);
    }
}
