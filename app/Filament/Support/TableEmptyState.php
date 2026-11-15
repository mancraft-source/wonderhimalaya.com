<?php

namespace App\Filament\Support;

use Closure;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Branded empty states for admin tables. Each table passes its own friendly
 * heading, description and icon; when the list is only empty because of a
 * tab, search or filter, the table says so instead of claiming nothing exists.
 */
final class TableEmptyState
{
    /** List pages name their unfiltered tab "all"; every other tab narrows the list. */
    public const UNFILTERED_TAB = 'all';

    public const NARROWED_DESCRIPTION = 'Nothing matches the current tab, search or filters. Try another tab or clear the filters.';

    /** @return Closure(): Heroicon */
    public static function icon(Table $table, Heroicon $icon): Closure
    {
        return fn (): Heroicon => self::isNarrowed($table) ? Heroicon::OutlinedFunnel : $icon;
    }

    /** @return Closure(): string */
    public static function heading(Table $table, string $heading): Closure
    {
        return fn (): string => self::isNarrowed($table)
            ? 'No matching '.$table->getPluralModelLabel()
            : $heading;
    }

    /** @return Closure(): string */
    public static function description(Table $table, string $description): Closure
    {
        return fn (): string => self::isNarrowed($table) ? self::NARROWED_DESCRIPTION : $description;
    }

    /**
     * Whether records exist but the current view hides them: any tab other than
     * "all", or a search or filter (including a default one, such as "Open"
     * tasks) while there are records underneath.
     */
    public static function isNarrowed(Table $table): bool
    {
        $livewire = $table->getLivewire();

        $isOnNarrowingTab = in_array(HasTabs::class, class_uses_recursive($livewire), true)
            && filled($livewire->activeTab)
            && $livewire->activeTab !== self::UNFILTERED_TAB;

        if (! $isOnNarrowingTab && $table->getFilterIndicators() === []) {
            return false;
        }

        $everything = $livewire instanceof ListRecords
            ? $livewire::getResource()::getEloquentQuery()
            : $table->getQuery();

        return (bool) $everything?->exists();
    }
}
