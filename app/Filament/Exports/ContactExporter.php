<?php

namespace App\Filament\Exports;

use App\Models\Contact;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

class ContactExporter extends CrmExporter
{
    protected static ?string $model = Contact::class;

    protected static string $recordNoun = 'contact';

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['company', 'owner'])->withCount(['leads', 'bookings']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('first_name')->label('First name'),
            ExportColumn::make('last_name')->label('Last name'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('whatsapp')->label('WhatsApp'),
            ExportColumn::make('country'),
            ExportColumn::make('company.name')->label('Company'),
            ExportColumn::make('source'),
            ExportColumn::make('marketing_consent')->label('Marketing consent')
                ->formatStateUsing(fn (Contact $record): string => $record->marketing_consent ? 'Yes' : 'No'),
            ExportColumn::make('consent_at')->label('Consent given')
                ->formatStateUsing(fn (Contact $record): ?string => static::date($record->consent_at, 'Y-m-d H:i'))
                ->enabledByDefault(false),
            ExportColumn::make('leads_count')->label('Leads'),
            ExportColumn::make('bookings_count')->label('Bookings'),
            ExportColumn::make('owner.name')->label('Owner'),
            ExportColumn::make('created_at')->label('Created')
                ->formatStateUsing(fn (Contact $record): ?string => static::date($record->created_at, 'Y-m-d H:i')),
        ];
    }
}
