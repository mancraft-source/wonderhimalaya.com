<?php

namespace App\Filament\Exports;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

/** Never exports the raw gateway payload. */
class PaymentExporter extends CrmExporter
{
    protected static ?string $model = Payment::class;

    protected static string $recordNoun = 'payment';

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['payable', 'contact']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('uuid')->label('Payment ID'),
            ExportColumn::make('payable')->label('For')
                ->state(fn (Payment $record): string => PaymentsTable::payableLabel($record)),
            ExportColumn::make('contact_name')->label('Contact name')
                ->state(fn (Payment $record): ?string => $record->contact?->name),
            ExportColumn::make('contact.email')->label('Email'),
            ExportColumn::make('type')
                ->formatStateUsing(fn (?PaymentType $state): ?string => $state?->getLabel()),
            ExportColumn::make('description')->enabledByDefault(false),
            ExportColumn::make('amount'),
            ExportColumn::make('currency'),
            ExportColumn::make('status')
                ->formatStateUsing(fn (?PaymentStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('gateway'),
            ExportColumn::make('gateway_reference')->label('Reference'),
            ExportColumn::make('paid_at')->label('Paid at')
                ->formatStateUsing(fn (Payment $record): ?string => static::date($record->paid_at, 'Y-m-d H:i')),
            ExportColumn::make('created_at')->label('Created')
                ->formatStateUsing(fn (Payment $record): ?string => static::date($record->created_at, 'Y-m-d H:i')),
        ];
    }
}
