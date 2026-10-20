<?php

namespace App\Filament\Resources\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Route;

final class QuoteActions
{
    /** Public, unguessable link the client opens (route owned by the public site). */
    public static function publicUrl(Quote $quote): string
    {
        return Route::has('quotes.show') ? route('quotes.show', $quote->uuid) : url('/quote/'.$quote->uuid);
    }

    public static function pdfUrl(Quote $quote): string
    {
        return Route::has('quotes.pdf') ? route('quotes.pdf', $quote->uuid) : url('/quote/'.$quote->uuid.'/pdf');
    }

    public static function preview(): Action
    {
        return Action::make('preview')
            ->label('Preview / public link')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (Quote $record): string => self::publicUrl($record))
            ->openUrlInNewTab();
    }

    public static function downloadPdf(): Action
    {
        return Action::make('download_pdf')
            ->label('Download PDF')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->url(fn (Quote $record): string => self::pdfUrl($record))
            ->openUrlInNewTab();
    }

    public static function markAsSent(): Action
    {
        return Action::make('mark_as_sent')
            ->label('Mark as sent')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('info')
            ->visible(fn (Quote $record): bool => $record->status === QuoteStatus::Draft)
            ->authorize(fn (Quote $record): bool => auth()->user()->can('update', $record))
            ->requiresConfirmation()
            ->action(function (Quote $record): void {
                $record->update(['status' => QuoteStatus::Sent, 'sent_at' => now()]);
                Notification::make()->success()->title('Quote marked as sent')->send();
            });
    }
}
