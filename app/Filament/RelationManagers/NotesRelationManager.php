<?php

namespace App\Filament\RelationManagers;

use App\Models\Note;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/** Internal notes on any "notable" CRM record (lead, contact, company, booking). */
class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    public function isReadOnly(): bool
    {
        return false;
    }

    /** Notes are written by people who may edit the record they are added to. */
    protected function getCreateAuthorizationResponse(): Response
    {
        return Gate::inspect('update', $this->getOwnerRecord());
    }

    protected function getEditAuthorizationResponse(Model $record): Response
    {
        return Gate::inspect('update', $this->getOwnerRecord());
    }

    protected function getDeleteAuthorizationResponse(Model $record): Response
    {
        return Gate::inspect('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->label('Note')->required()->rows(5)->maxLength(10000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('body')->label('Note')->wrap()->limit(400),
                TextColumn::make('user.name')->label('By')->placeholder('System'),
                TextColumn::make('created_at')->since()->dateTimeTooltip()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add note')
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (Note $record): bool => $record->user_id === auth()->id() || (bool) auth()->user()?->isAdmin()),
                DeleteAction::make()->visible(fn (Note $record): bool => $record->user_id === auth()->id() || (bool) auth()->user()?->isAdmin()),
            ]);
    }
}
