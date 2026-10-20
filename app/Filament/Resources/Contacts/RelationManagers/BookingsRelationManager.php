<?php

namespace App\Filament\Resources\Contacts\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedTicket;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->modifyQueryUsing(fn ($query) => $query->with('trip'))
            ->defaultSort('start_date', 'desc')
            ->recordUrl(fn (Booking $record): string => BookingResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->weight('medium'),
                TextColumn::make('trip.title')->label('Trip')->limit(40),
                TextColumn::make('start_date')->date(),
                TextColumn::make('status')->badge(),
                TextColumn::make('total')->money(fn (Booking $record): string => $record->currency ?: 'USD'),
                TextColumn::make('amount_paid')->label('Paid')->money(fn (Booking $record): string => $record->currency ?: 'USD'),
            ]);
    }
}
