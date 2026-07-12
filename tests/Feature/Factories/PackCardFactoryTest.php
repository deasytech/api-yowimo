<?php

use App\Enums\PackCardKind;
use App\Models\PackCard;

it('never produces duplicated terminal punctuation', function () {
    $cards = PackCard::factory()->count(30)->make();

    foreach ($cards as $card) {
        expect($card->text)->not->toMatch('/[.!?]{2,}$/');

        if ($card->kind === PackCardKind::Truth) {
            expect($card->text)->toEndWith('?');
        } else {
            expect($card->text)->toEndWith('.');
        }
    }
});
