<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Filament\Exports\BookingExporter;
use App\Filament\Support\ExportActions;
use App\Filament\Support\TableEmptyState;
use App\Models\Booking;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        $money = fn (Booking $record): string => $record->currency ?: 'USD';

        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedTicket))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No bookings yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'A booking is created automatically when a client accepts a quote online, and you can also add one by hand.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contact', 'trip']))
            ->defaultSort('start_date')
            ->columns([
                TextColumn::make('number')->searchable()->sortable()->weight('medium')->copyable(),
                TextColumn::make('contact.email')
                    ->label('Contact')
                    ->formatStateUsing(fn (Booking $record): string => $record->contact?->name ?: (string) $record->contact?->email)
                    ->description(fn (Booking $record): ?string => $record->contact?->email)
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('trip.title')->label('Trip')->limit(36)->tooltip(fn (Booking $record): ?string => $record->trip?->title)->toggleable(),
                TextColumn::make('start_date')->date('j M Y')->sortable(),
                TextColumn::make('pax')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('total')->money($money)->sortable(),
                TextColumn::make('amount_paid')->label('Paid')->money($money)->sortable(),
                TextColumn::make('balance')
                    ->state(fn (Booking $record): float => $record->balanceDue())
                    ->money($money)
                    ->color(fn (Booking $record): ?string => $record->balanceDue() > 0 && $record->balance_due_on?->isPast() ? 'danger' : null),
                TextColumn::make('balance_due_on')->label('Balance due')->date('j M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(BookingStatus::class)->multiple(),
                SelectFilter::make('trip_id')->label('Trip')->relationship('trip', 'title')->searchable(),
                TernaryFilter::make('balance_outstanding')
                    ->label('Balance outstanding')
                    ->queries(
                        true: fn (Builder $q) => $q->whereColumn('amount_paid', '<', 'total'),
                        false: fn (Builder $q) => $q->whereColumn('amount_paid', '>=', 'total'),
                    ),
                TrashedFilter::make(),
            ])
            ->headerActions([
                ExportActions::header(BookingExporter::class),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportActions::bulk(BookingExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
