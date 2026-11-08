<?php

namespace App\Filament\Resources\NotFoundLogs\Tables;

use App\Filament\Resources\Redirects\Schemas\RedirectForm;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotFoundLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('path')
                    ->searchable()
                    ->copyable()
                    ->limit(70)
                    ->url(fn (NotFoundLog $record): string => url($record->path), shouldOpenInNewTab: true)
                    ->description(fn (NotFoundLog $record): ?string => $record->is_ignored ? 'Ignored' : null),
                TextColumn::make('hits')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label('Last hit')->since()->sortable(),
                TextColumn::make('referrer')
                    ->label('Linked from')
                    ->limit(45)
                    ->tooltip(fn (NotFoundLog $record): ?string => $record->referrer)
                    ->placeholder('Direct / unknown')
                    ->toggleable(),
                TextColumn::make('user_agent')->label('Browser / bot')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('First seen')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_ignored')
                    ->label('Ignored')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_ignored', true),
                        false: fn (Builder $query) => $query->where('is_ignored', false),
                    ),
            ])
            ->recordActions([
                Action::make('redirect')
                    ->label('Redirect')
                    ->icon(Heroicon::OutlinedArrowUturnRight)
                    ->color('primary')
                    ->modalHeading(fn (NotFoundLog $record): string => 'Redirect '.$record->path)
                    ->schema([
                        TextInput::make('to_path')
                            ->label('Send visitors to')
                            ->placeholder('/trip/everest-base-camp-trek or https://…')
                            ->required()
                            ->maxLength(500),
                        Select::make('status_code')
                            ->label('Type')
                            ->options(RedirectForm::STATUS_CODES)
                            ->default(301)
                            ->selectablePlaceholder(false),
                    ])
                    ->action(function (NotFoundLog $record, array $data): void {
                        Redirect::point($record->path, $data['to_path'], (int) $data['status_code']);
                        $record->delete();
                        Notification::make()->title('Redirect created')->body("{$record->path} → {$data['to_path']}")->success()->send();
                    }),
                Action::make('ignore')
                    ->label(fn (NotFoundLog $record): string => $record->is_ignored ? 'Restore' : 'Ignore')
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->color('gray')
                    ->action(fn (NotFoundLog $record) => $record->update(['is_ignored' => ! $record->is_ignored])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('redirectAll')
                        ->label('Redirect selected to…')
                        ->icon(Heroicon::OutlinedArrowUturnRight)
                        ->schema([
                            TextInput::make('to_path')->label('Send visitors to')->required()->maxLength(500),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (NotFoundLog $log) => Redirect::point($log->path, $data['to_path']));
                            $records->each->delete();
                            Notification::make()->title($records->count().' redirects created')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('ignoreAll')
                        ->label('Ignore selected')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => NotFoundLog::whereKey($records->modelKeys())->update(['is_ignored' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
