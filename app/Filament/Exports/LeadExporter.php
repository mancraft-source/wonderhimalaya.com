<?php

namespace App\Filament\Exports;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

class LeadExporter extends CrmExporter
{
    protected static ?string $model = Lead::class;

    protected static string $recordNoun = 'lead';

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['contact', 'trip', 'owner']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference'),
            ExportColumn::make('created_at')->label('Received')
                ->formatStateUsing(fn (Lead $record): ?string => static::date($record->created_at, 'Y-m-d H:i')),
            ExportColumn::make('status')
                ->formatStateUsing(fn (?LeadStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('type')
                ->formatStateUsing(fn (?LeadType $state): ?string => $state?->getLabel()),
            ExportColumn::make('contact_name')->label('Contact name')
                ->state(fn (Lead $record): ?string => $record->contact?->name),
            ExportColumn::make('contact.email')->label('Email'),
            ExportColumn::make('contact.phone')->label('Phone'),
            ExportColumn::make('contact.country')->label('Country'),
            ExportColumn::make('trip.title')->label('Trip'),
            ExportColumn::make('travellers')->label('Travellers')
                ->state(fn (Lead $record): int => $record->travellerCount()),
            ExportColumn::make('start_date')->label('Start date')
                ->state(fn (Lead $record): ?string => static::date($record->start_date) ?? $record->travel_month),
            ExportColumn::make('estimated_value')->label('Estimated value'),
            ExportColumn::make('currency'),
            ExportColumn::make('source'),
            ExportColumn::make('owner.name')->label('Owner'),
            ExportColumn::make('next_follow_up_at')->label('Next follow-up')
                ->formatStateUsing(fn (Lead $record): ?string => static::date($record->next_follow_up_at, 'Y-m-d H:i'))
                ->enabledByDefault(false),
            ExportColumn::make('lost_reason')->label('Lost reason')->enabledByDefault(false),
        ];
    }
}
