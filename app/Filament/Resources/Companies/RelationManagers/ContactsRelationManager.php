<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Support\CrmFields;
use App\Models\Contact;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedUsers;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(CrmFields::contactFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->recordUrl(fn (Contact $record): string => ContactResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('first_name')->label('Name')->formatStateUsing(fn (Contact $record): string => $record->name),
                TextColumn::make('email')->copyable(),
                TextColumn::make('phone'),
                TextColumn::make('country'),
            ])
            ->headerActions([CreateAction::make()]);
    }
}
