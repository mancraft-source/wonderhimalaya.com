<?php

namespace App\Filament\Support;

use App\Support\Preview;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Links from content records (trips, pages, posts, destinations, activities, team) to the public site:
 * "View on site" for live records, a shareable signed "Preview" for drafts and scheduled records.
 */
final class SiteLinkActions
{
    /** @return array<int, Action> Header actions for edit pages. */
    public static function header(): array
    {
        return [self::viewOnSite(), self::preview()];
    }

    public static function viewOnSite(): Action
    {
        return Action::make('view_on_site')
            ->label('View on site')
            ->color('gray')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->url(fn (Model $record): string => Preview::publicUrl($record), shouldOpenInNewTab: true)
            ->visible(fn (Model $record): bool => Preview::isPublic($record));
    }

    public static function preview(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->color('gray')
            ->icon(Heroicon::OutlinedEye)
            ->tooltip('Not published yet. Opens a private preview link (valid '.Preview::VALID_FOR_HOURS.'h) you can share for review.')
            ->url(fn (Model $record): string => Preview::signedUrl($record), shouldOpenInNewTab: true)
            ->visible(fn (Model $record): bool => Preview::isPreviewable($record));
    }

    /** Compact icon for table rows; only shown for records that are live. */
    public static function openOnSite(): Action
    {
        return self::viewOnSite()
            ->name('open_on_site')
            ->label('Open on site')
            ->tooltip('Open on site')
            ->iconButton();
    }
}
