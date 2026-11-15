<?php

namespace App\Filament\Exports;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

/** Deliberately leaves out emergency contacts, special requests and internal notes. */
class BookingExporter extends CrmExporter
{
    protected static ?string $model = Booking::class;

    protected static string $recordNoun = 'booking';

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['contact', 'trip', 'owner']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('number')->label('Booking'),
            ExportColumn::make('status')
                ->formatStateUsing(fn (?BookingStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('contact_name')->label('Contact name')
                ->state(fn (Booking $record): ?string => $record->contact?->name),
            ExportColumn::make('contact.email')->label('Email'),
            ExportColumn::make('contact.phone')->label('Phone'),
            ExportColumn::make('trip.title')->label('Trip'),
            ExportColumn::make('start_date')->label('Start date')
                ->formatStateUsing(fn (Booking $record): ?string => static::date($record->start_date)),
            ExportColumn::make('end_date')->label('End date')
                ->formatStateUsing(fn (Booking $record): ?string => static::date($record->end_date)),
            ExportColumn::make('pax')->label('Travellers'),
            ExportColumn::make('currency'),
            ExportColumn::make('total'),
            ExportColumn::make('deposit_amount')->label('Deposit'),
            ExportColumn::make('amount_paid')->label('Paid'),
            ExportColumn::make('balance')->label('Balance due')
                ->state(fn (Booking $record): float => $record->balanceDue()),
            ExportColumn::make('balance_due_on')->label('Balance due on')
                ->formatStateUsing(fn (Booking $record): ?string => static::date($record->balance_due_on)),
            ExportColumn::make('owner.name')->label('Owner'),
            ExportColumn::make('confirmed_at')->label('Confirmed')
                ->formatStateUsing(fn (Booking $record): ?string => static::date($record->confirmed_at, 'Y-m-d H:i'))
                ->enabledByDefault(false),
            ExportColumn::make('created_at')->label('Created')
                ->formatStateUsing(fn (Booking $record): ?string => static::date($record->created_at, 'Y-m-d H:i')),
        ];
    }
}
