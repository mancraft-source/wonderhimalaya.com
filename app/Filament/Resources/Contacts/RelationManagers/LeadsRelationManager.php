<?php

namespace App\Filament\Resources\Contacts\RelationManagers;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Read-only list of a contact's / company's enquiries linking to the lead page. */
class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedInboxArrowDown;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->modifyQueryUsing(fn ($query) => LeadResource::scopeVisibleTo($query->with(['trip', 'owner']), auth()->user()))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')->weight('medium'),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('trip.title')->label('Trip')->placeholder('Tailor-made')->limit(40),
                TextColumn::make('owner.name')->label('Owner')->placeholder('Unassigned'),
                TextColumn::make('created_at')->since(),
            ]);
    }
}
