<?php

namespace App\Filament\Widgets;

use App\Enums\DepartureStatus;
use App\Filament\Resources\TripDepartures\TripDepartureResource;
use App\Models\TripDeparture;
use App\Support\Permissions;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Dashboard: every scheduled group departure in the coming weeks (30, 60 or 90 days; 60 by default). */
class UpcomingDepartures extends TableWidget
{
    /** Range choices in days; the dashboard never shows less than a month ahead. */
    public const RANGES = [30 => 'Next 30 days', 60 => 'Next 60 days', 90 => 'Next 90 days'];

    public const DEFAULT_RANGE = 60;

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
            ->heading('Upcoming departures')
            ->query(fn () => TripDeparture::query()
                ->with(['trip', 'tier'])
                ->whereDate('start_date', '>=', today())
                ->whereNot('status', DepartureStatus::Cancelled))
            ->filters([
                SelectFilter::make('range')
                    ->label('Show')
                    ->options(self::RANGES)
                    ->default((string) self::DEFAULT_RANGE)
                    ->selectablePlaceholder(false)
                    ->query(fn (Builder $query, array $data): Builder => $query->whereDate(
                        'start_date', '<=', today()->addDays(array_key_exists((int) ($data['value'] ?? 0), self::RANGES) ? (int) $data['value'] : self::DEFAULT_RANGE)
                    )),
            ])
            ->persistFiltersInSession()
            ->defaultSort('start_date')
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->recordUrl(fn (TripDeparture $record): ?string => auth()->user()->can('update', $record)
                ? TripDepartureResource::getUrl('edit', ['record' => $record])
                : null)
            ->emptyStateIcon(Heroicon::OutlinedCalendarDays)
            ->emptyStateHeading('No departures in this period')
            ->emptyStateDescription('Fixed group departures you schedule on a trip appear here. Try a longer range.')
            ->columns([
                TextColumn::make('start_date')->label('Starts')->date('D j M Y')->sortable()
                    ->description(fn (TripDeparture $record): string => $record->start_date->isToday() ? 'Today' : 'in '.today()->diffInDays($record->start_date).' days'),
                TextColumn::make('trip.title')->label('Trip')->wrap()->searchable(),
                TextColumn::make('tier.name')->label('Tier')->placeholder('—'),
                TextColumn::make('seats_booked')->label('Booked')
                    ->formatStateUsing(fn (TripDeparture $record): string => $record->seats_booked.' / '.$record->seats_total),
                TextColumn::make('seats_left')
                    ->label('Seats left')
                    ->state(fn (TripDeparture $record): int => $record->seatsLeft())
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),
                TextColumn::make('status')->badge(),
            ]);
    }
}
