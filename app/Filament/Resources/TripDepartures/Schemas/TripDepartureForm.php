<?php

namespace App\Filament\Resources\TripDepartures\Schemas;

use App\Enums\DepartureStatus;
use App\Models\Tier;
use App\Models\Trip;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TripDepartureForm
{
    public static function configure(Schema $schema, bool $withTrip = true): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                ...($withTrip ? [
                    Select::make('trip_id')
                        ->relationship('trip', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                ] : []),
                DatePicker::make('start_date')
                    ->required()
                    ->native(false)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state, $livewire) use ($withTrip): void {
                        $trip = $withTrip
                            ? Trip::find($get('trip_id'))
                            : (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null);

                        if ($state && $trip?->duration_days) {
                            $set('end_date', CarbonImmutable::parse($state)->addDays($trip->duration_days - 1)->toDateString());
                        }
                    }),
                DatePicker::make('end_date')
                    ->native(false)
                    ->afterOrEqual('start_date')
                    ->helperText('Filled from the trip duration when you pick a start date.'),
                Select::make('tier_id')
                    ->label('Tier')
                    ->options(fn () => Tier::query()->orderBy('sort')->pluck('name', 'id')),
                Select::make('status')
                    ->options(DepartureStatus::class)
                    ->default(DepartureStatus::Available)
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Per person. Leave blank to use the trip price.'),
                TextInput::make('sale_price')->numeric()->minValue(0),
                TextInput::make('seats_total')->numeric()->required()->default(12)->minValue(0),
                TextInput::make('seats_booked')->numeric()->required()->default(0)->minValue(0)->lte('seats_total'),
                TextInput::make('note')->maxLength(255)->columnSpanFull(),
            ]);
    }
}
