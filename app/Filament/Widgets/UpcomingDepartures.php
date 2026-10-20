<?php

namespace App\Filament\Widgets;

use App\Enums\DepartureStatus;
use App\Filament\Resources\TripDepartures\TripDepartureResource;
use App\Models\TripDeparture;
use App\Support\Permissions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingDepartures extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can(Permissions::CATALOGUE) || $user?->can(Permissions::CRM_VIEW));
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Departures in the next 60 days')
            ->query(fn () => TripDeparture::query()
                ->with(['trip', 'tier'])
                ->whereDate('start_date', '>=', today())
                ->whereDate('start_date', '<=', today()->addDays(60))
                ->whereNot('status', DepartureStatus::Cancelled))
            ->defaultSort('start_date')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (TripDeparture $record): ?string => auth()->user()->can('update', $record)
                ? TripDepartureResource::getUrl('edit', ['record' => $record])
                : null)
            ->columns([
                TextColumn::make('start_date')->date('D j M')->sortable(),
                TextColumn::make('trip.title')->label('Trip')->wrap(),
                TextColumn::make('tier.name')->label('Tier')->placeholder('—'),
                TextColumn::make('seats_left')
                    ->label('Seats left')
                    ->state(fn (TripDeparture $record): int => $record->seatsLeft())
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),
                TextColumn::make('seats_booked')->label('Booked')->formatStateUsing(fn (TripDeparture $record): string => $record->seats_booked.' / '.$record->seats_total),
                TextColumn::make('status')->badge(),
            ]);
    }
}
