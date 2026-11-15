<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Resources\Trips\TripResource;
use App\Filament\Widgets\LeadsBySourceChart;
use App\Filament\Widgets\LeadsTrendChart;
use App\Filament\Widgets\RecentEnquiries;
use App\Filament\Widgets\RevenueOverview;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TodayOverview;
use App\Filament\Widgets\UpcomingDepartures;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Branded home screen: a time-of-day greeting, quick actions and the widgets
 * each member of staff is allowed to see (every widget checks its own permission).
 */
class Dashboard extends BaseDashboard
{
    public const TIMEZONE = 'Asia/Kathmandu';

    public function getTitle(): string
    {
        return 'Dashboard';
    }

    public function getHeading(): string
    {
        $firstName = Str::before(trim((string) auth()->user()?->name), ' ');

        return self::greeting(now(self::TIMEZONE)).($firstName !== '' ? ', '.$firstName : '');
    }

    public function getSubheading(): string
    {
        return now(self::TIMEZONE)->format('l j F Y').' · Kathmandu';
    }

    public static function greeting(Carbon $time): string
    {
        return match (true) {
            $time->hour < 5 => 'Good evening',
            $time->hour < 12 => 'Good morning',
            $time->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    /** @return array<class-string> */
    public function getWidgets(): array
    {
        return [
            TodayOverview::class,
            StatsOverview::class,
            RevenueOverview::class,
            RecentEnquiries::class,
            UpcomingDepartures::class,
            LeadsTrendChart::class,
            LeadsBySourceChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newLead')
                ->label('New lead')
                ->icon(Heroicon::OutlinedUserPlus)
                ->url(fn (): string => LeadResource::getUrl('create'))
                ->visible(fn (): bool => LeadResource::canCreate()),
            Action::make('newQuote')
                ->label('New quote')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->url(fn (): string => QuoteResource::getUrl('create'))
                ->visible(fn (): bool => QuoteResource::canCreate()),
            Action::make('newTrip')
                ->label('New trip')
                ->icon(Heroicon::OutlinedMap)
                ->color('gray')
                ->url(fn (): string => TripResource::getUrl('create'))
                ->visible(fn (): bool => TripResource::canCreate()),
            Action::make('viewSite')
                ->label('View site')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('home'), shouldOpenInNewTab: true),
        ];
    }
}
