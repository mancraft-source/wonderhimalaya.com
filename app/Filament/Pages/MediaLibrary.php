<?php

namespace App\Filament\Pages;

use App\Filament\Support\TableEmptyState;
use App\Support\Media\MediaUsage;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use UnitEnum;

/**
 * Content → Media library: every image uploaded to trips, destinations, pages, posts, team members…,
 * where each one is used, its size and alt text. Alt text can be edited in bulk; only unused files can be deleted.
 */
class MediaLibrary extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Media library';

    protected static ?int $navigationSort = 70;

    protected static ?string $title = 'Media library';

    protected static ?string $slug = 'media';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::CONTENT) ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Media::query()->where('mime_type', 'like', 'image/%')->with('model'))
            ->modelLabel('image')
            ->pluralModelLabel('images')
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedPhoto))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No images yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Images uploaded to trips, destinations, pages, posts and team members appear here.'))
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->state(fn (Media $record): string => MediaUsage::thumbnailUrl($record))
                    ->imageHeight(48)
                    ->imageWidth(64)
                    ->extraImgAttributes(['loading' => 'lazy', 'class' => 'object-cover rounded']),
                TextColumn::make('file_name')
                    ->label('File')
                    ->searchable(['file_name', 'name'])
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (Media $record): string => $record->file_name)
                    ->description(fn (Media $record): ?string => MediaUsage::dimensions($record)),
                TextColumn::make('size')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => Number::fileSize($state, precision: 1).($state > MediaUsage::LARGE_BYTES ? ' · large' : ''))
                    ->color(fn (int $state): string => $state > MediaUsage::LARGE_BYTES ? 'warning' : 'gray'),
                TextColumn::make('collection_name')
                    ->label('Collection')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('used_by')
                    ->label('Used on')
                    ->state(function (Media $record): string {
                        $owner = MediaUsage::owner($record);

                        return $owner ? MediaUsage::ownerTitle($owner) : 'Not used';
                    })
                    ->description(function (Media $record): string {
                        $owner = MediaUsage::owner($record);
                        $inTrash = $owner && method_exists($owner, 'trashed') && $owner->trashed();

                        return MediaUsage::ownerTypeLabel($record->model_type).($inTrash ? ' (in trash)' : '');
                    })
                    ->color(fn (Media $record): ?string => MediaUsage::isUsed($record) ? 'primary' : 'danger')
                    ->url(fn (Media $record): ?string => ($owner = MediaUsage::owner($record)) ? MediaUsage::ownerUrl($owner) : null)
                    ->limit(40),
                TextColumn::make('alt')
                    ->label('Alt text')
                    ->state(fn (Media $record): ?string => MediaUsage::alt($record))
                    ->placeholder('Missing')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('collection_name')
                    ->label('Collection')
                    ->options(fn (): array => Media::query()->distinct()->orderBy('collection_name')->pluck('collection_name', 'collection_name')->all()),
                SelectFilter::make('model_type')
                    ->label('Used on')
                    ->options(fn (): array => Media::query()->distinct()->orderBy('model_type')->pluck('model_type')
                        ->mapWithKeys(fn (string $type): array => [$type => MediaUsage::ownerTypeLabel($type)])->all()),
                Filter::make('large')
                    ->label('Large files (over 1 MB)')
                    ->query(fn (Builder $query): Builder => $query->where('size', '>', MediaUsage::LARGE_BYTES)),
                Filter::make('missing_alt')
                    ->label('Missing alt text')
                    ->query(fn (Builder $query): Builder => MediaUsage::whereMissingAlt($query)),
                Filter::make('unused')
                    ->label('Not used anywhere')
                    ->query(fn (Builder $query): Builder => MediaUsage::whereUnused($query)),
            ])
            ->recordActions([
                Action::make('editAlt')
                    ->label('Alt text')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('Edit alt text')
                    ->modalDescription('Describes the image for screen readers and search engines. Shown wherever the website uses this image.')
                    ->fillForm(fn (Media $record): array => ['alt' => MediaUsage::alt($record)])
                    ->schema([$this->altTextInput()])
                    ->action(function (Media $record, array $data): void {
                        $this->saveAlt($record, $data['alt'] ?? null);
                        Notification::make()->title('Alt text saved')->success()->send();
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->visible(fn (Media $record): bool => ! MediaUsage::isUsed($record))
                    ->requiresConfirmation()
                    ->modalHeading('Delete this unused image?')
                    ->modalDescription('The file and its resized versions are removed from storage. This cannot be undone.')
                    ->action(function (Media $record): void {
                        if (MediaUsage::isUsed($record)) {
                            Notification::make()->title('This image is in use and was not deleted')->warning()->send();

                            return;
                        }

                        $record->delete();
                        Notification::make()->title('Image deleted')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('editAltText')
                        ->label('Set alt text')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->modalHeading('Set alt text for the selected images')
                        ->modalDescription('The same alt text is saved on every selected image. Leave empty to clear it (the website then falls back to the page title).')
                        ->schema([$this->altTextInput()])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (Media $media) => $this->saveAlt($media, $data['alt'] ?? null));
                            Notification::make()->title('Alt text saved on '.$records->count().' '.str('image')->plural($records->count()))->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deleteUnused')
                        ->label('Delete unused')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete the selected unused images?')
                        ->modalDescription('Only images that are not used anywhere are deleted; images in use are skipped. Files are removed from storage and cannot be restored.')
                        ->action(function (Collection $records): void {
                            [$unused, $used] = $records->partition(fn (Media $media): bool => ! MediaUsage::isUsed($media));
                            $unused->each(fn (Media $media) => $media->delete());

                            Notification::make()
                                ->title('Deleted '.$unused->count().' unused '.str('image')->plural($unused->count()))
                                ->body($used->isNotEmpty() ? $used->count().' in use '.($used->count() === 1 ? 'was' : 'were').' kept.' : null)
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private function altTextInput(): TextInput
    {
        return TextInput::make('alt')
            ->label('Alt text')
            ->maxLength(250)
            ->placeholder('e.g. Trekkers crossing a suspension bridge below Ama Dablam');
    }

    private function saveAlt(Media $media, ?string $alt): void
    {
        $media->setCustomProperty(MediaUsage::ALT_PROPERTY, trim((string) $alt))->save();
    }
}
