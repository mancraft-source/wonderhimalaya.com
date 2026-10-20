<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedCreditCard;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can(Permissions::PAYMENTS);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('uuid')
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')->money(fn (Payment $record): string => $record->currency ?: 'USD'),
                TextColumn::make('status')->badge(),
                TextColumn::make('gateway')->placeholder('Any'),
                TextColumn::make('gateway_reference')->label('Reference')->placeholder('—')->toggleable(),
                TextColumn::make('expires_at')->dateTime('j M Y')->placeholder('—'),
                TextColumn::make('paid_at')->dateTime('j M Y, H:i')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('copy_link')
                    ->label('Link')
                    ->icon(Heroicon::OutlinedLink)
                    ->url(fn (Payment $record): string => PaymentResource::payUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (Payment $record): bool => $record->isPayable()),
                PaymentsTable::markAsPaidAction(),
            ]);
    }
}
