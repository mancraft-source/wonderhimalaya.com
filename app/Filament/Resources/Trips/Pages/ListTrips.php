<?php

namespace App\Filament\Resources\Trips\Pages;

use App\Enums\PublishStatus;
use App\Filament\Resources\Trips\TripResource;
use App\Filament\Support\ListTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTrips extends ListRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $trips = TripResource::getEloquentQuery();

        return [
            'all' => Tab::make('All'),
            'published' => ListTabs::counted('Published', $trips, fn (Builder $query): Builder => $query->published(), 'success'),
            'drafts' => ListTabs::counted('Drafts', $trips, fn (Builder $query): Builder => $query->where('status', PublishStatus::Draft)),
            'fixed' => ListTabs::counted('Fixed departures', $trips, fn (Builder $query): Builder => $query->where('is_fixed_departure', true), 'info'),
        ];
    }
}
