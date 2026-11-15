<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Support\ListTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Upcoming comes first so it is the default view, as the old "When" filter was.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $bookings = BookingResource::getEloquentQuery();

        return [
            'upcoming' => ListTabs::counted('Upcoming', $bookings, fn (Builder $query): Builder => $query
                ->where(fn (Builder $query) => $query->whereNull('start_date')->orWhereDate('start_date', '>=', today()))),
            'departing_soon' => ListTabs::counted('Departing in 30 days', $bookings, fn (Builder $query): Builder => $query
                ->whereDate('start_date', '>=', today())
                ->whereDate('start_date', '<=', today()->addDays(30))
                ->where('status', '!=', BookingStatus::Cancelled), 'warning'),
            'balance_due' => ListTabs::counted('Balance due', $bookings, fn (Builder $query): Builder => $query
                ->whereColumn('amount_paid', '<', 'total')
                ->where('status', '!=', BookingStatus::Cancelled), 'danger'),
            'past' => ListTabs::counted('Past', $bookings, fn (Builder $query): Builder => $query->whereDate('start_date', '<', today())),
            'all' => Tab::make('All'),
        ];
    }
}
