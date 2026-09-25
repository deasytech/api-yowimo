<?php

namespace App\Filament\Support;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

/**
 * Sections repeated identically across multiple resource infolists.
 */
class InfolistSections
{
    public static function visibilityAndOrdering(): Section
    {
        return Section::make('Visibility & Ordering')
            ->icon(Heroicon::OutlinedEye)
            ->columns(3)
            ->schema([
                IconEntry::make('is_featured')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('sort_order'),
            ]);
    }

    public static function timestamps(): Section
    {
        return Section::make('Timestamps')
            ->icon(Heroicon::OutlinedClock)
            ->columns(2)
            ->collapsible()
            ->schema([
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
