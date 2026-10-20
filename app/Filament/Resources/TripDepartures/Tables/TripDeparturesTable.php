<?php

namespace App\Filament\Resources\TripDepartures\Tables;

use App\Enums\DepartureStatus;
use App\Models\TripDeparture;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TripDeparturesTable
{
    public static function configure(Table $table, bool $withTrip = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['trip', 'tier']))
            ->defaultSort('start_date')
            ->columns([
                ...($withTrip ? [
                    TextColumn::make('trip.title')
                        ->label('Trip')
                        ->searchable()
                        ->sortable()
                        ->wrap(),
                ] : []),
                TextColumn::make('start_date')->date('D j M Y')->sortable(),
                TextColumn::make('end_date')->date('j M Y')->sortable()->toggleable(),
                TextColumn::make('tier.name')->label('Tier')->toggleable(),
                TextColumn::make('effective_price')
                    ->label('Price')
                    ->state(fn (TripDeparture $record): ?float => $record->effectivePrice())
                    ->money(fn (TripDeparture $record): string => $record->trip?->currency ?: 'USD'),
                TextColumn::make('seats')
                    ->label('Seats')
                    ->state(fn (TripDeparture $record): string => $record->seats_booked.' / '.$record->seats_total)
                    ->description(fn (TripDeparture $record): string => $record->seatsLeft().' left')
                    ->color(fn (TripDeparture $record): ?string => $record->seatsLeft() === 0 ? 'danger' : ($record->seatsLeft() <= 3 ? 'warning' : null)),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('note')->limit(30)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('upcoming')
                    ->label('When')
                    ->placeholder('All dates')
                    ->trueLabel('Upcoming')
                    ->falseLabel('Past')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereDate('start_date', '>=', today()),
                        false: fn (Builder $query) => $query->whereDate('start_date', '<', today()),
                        blank: fn (Builder $query) => $query,
                    ),
                SelectFilter::make('status')->options(DepartureStatus::class)->multiple(),
                ...($withTrip ? [
                    SelectFilter::make('trip_id')->label('Trip')->relationship('trip', 'title')->searchable()->preload(),
                ] : []),
                SelectFilter::make('tier_id')->label('Tier')->relationship('tier', 'name'),
                Filter::make('has_seats')
                    ->label('Seats available')
                    ->query(fn (Builder $query) => $query->whereColumn('seats_booked', '<', 'seats_total')),
                Filter::make('start_between')
                    ->schema([
                        DatePicker::make('from')->native(false),
                        DatePicker::make('until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_date', '<=', $date))),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
