<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedDocumentCurrencyDollar;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Quote $record): string => QuoteResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->weight('medium'),
                TextColumn::make('title')->wrap(),
                TextColumn::make('status')->badge(),
                TextColumn::make('pax'),
                TextColumn::make('total')->money(fn (Quote $record): string => $record->currency ?: 'USD'),
                TextColumn::make('valid_until')->date(),
                TextColumn::make('created_at')->since(),
            ])
            ->recordActions([
                Action::make('open')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Quote $record): string => QuoteResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
