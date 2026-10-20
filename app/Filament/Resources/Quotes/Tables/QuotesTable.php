<?php

namespace App\Filament\Resources\Quotes\Tables;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\QuoteActions;
use App\Models\Quote;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contact', 'lead', 'trip']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')->searchable()->sortable()->weight('medium')->copyable(),
                TextColumn::make('title')->searchable()->wrap()->description(fn (Quote $record): ?string => $record->lead?->reference),
                TextColumn::make('contact.email')
                    ->label('Contact')
                    ->formatStateUsing(fn (Quote $record): string => $record->contact?->name ?: (string) $record->contact?->email)
                    ->description(fn (Quote $record): ?string => $record->contact?->email)
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('pax')->toggleable(),
                TextColumn::make('total')->money(fn (Quote $record): string => $record->currency ?: 'USD')->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->color(fn (Quote $record): ?string => $record->isExpired() ? 'danger' : null),
                TextColumn::make('sent_at')->since()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(QuoteStatus::class)->multiple(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    QuoteActions::preview(),
                    QuoteActions::downloadPdf(),
                    QuoteActions::markAsSent(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
