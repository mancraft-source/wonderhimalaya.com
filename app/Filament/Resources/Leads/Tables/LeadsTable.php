<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Support\CrmFields;
use App\Models\Lead;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contact', 'trip', 'owner']))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),
                TextColumn::make('contact.first_name')
                    ->label('Contact')
                    ->formatStateUsing(fn (Lead $record): string => $record->contact?->name ?? '—')
                    ->description(fn (Lead $record): ?string => $record->contact?->email)
                    ->searchable(['first_name', 'last_name', 'email', 'phone']),
                TextColumn::make('type')->badge()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('trip.title')
                    ->label('Trip')
                    ->placeholder('Tailor-made')
                    ->limit(32)
                    ->tooltip(fn (Lead $record): ?string => $record->trip?->title)
                    ->toggleable(),
                TextColumn::make('adults')
                    ->label('Pax')
                    ->formatStateUsing(fn (Lead $record): string => (string) $record->travellerCount())
                    ->toggleable(),
                TextColumn::make('start_date')
                    ->label('Travel')
                    ->state(fn (Lead $record): ?string => $record->start_date?->format('j M Y') ?? $record->travel_month)
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('estimated_value')
                    ->label('Value')
                    ->money(fn (Lead $record): string => $record->currency ?: 'USD')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('created_at')->label('Received')->since()->sortable()->dateTimeTooltip(),
                TextColumn::make('next_follow_up_at')
                    ->label('Follow-up')
                    ->dateTime('j M, H:i')
                    ->sortable()
                    ->color(fn (Lead $record): ?string => $record->next_follow_up_at?->isPast() ? 'danger' : null)
                    ->icon(fn (Lead $record) => $record->next_follow_up_at?->isPast() ? Heroicon::ExclamationCircle : null)
                    ->toggleable(),
                TextColumn::make('source')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::class)->multiple(),
                SelectFilter::make('type')->options(LeadType::class)->multiple(),
                Filter::make('owner')
                    ->schema([
                        Select::make('owner')
                            ->label('Owner')
                            ->options(fn () => ['mine' => 'Mine', 'unassigned' => 'Unassigned'] + CrmFields::staffOptions()->all()),
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['owner'] ?? null) {
                        null, '' => $query,
                        'mine' => $query->where('owner_id', auth()->id()),
                        'unassigned' => $query->whereNull('owner_id'),
                        default => $query->where('owner_id', $data['owner']),
                    })
                    ->indicateUsing(fn (array $data): ?string => match ($data['owner'] ?? null) {
                        null, '' => null,
                        'mine' => 'Owner: me',
                        'unassigned' => 'Unassigned',
                        default => 'Owner: '.(CrmFields::staffOptions()[$data['owner']] ?? '?'),
                    }),
                Filter::make('created_between')
                    ->schema([
                        DatePicker::make('from')->native(false),
                        DatePicker::make('until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
                SelectFilter::make('trip_id')->label('Trip')->relationship('trip', 'title')->searchable(),
                SelectFilter::make('source')
                    ->options(fn () => Lead::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source', 'source')->all()),
                Filter::make('overdue')
                    ->label('Follow-up overdue')
                    ->query(fn (Builder $query) => $query->where('next_follow_up_at', '<', now())),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign_to_me')
                        ->label('Assign to me')
                        ->icon(Heroicon::OutlinedUserPlus)
                        ->action(fn (Collection $records) => $records
                            ->filter(fn (Lead $lead) => auth()->user()->can('update', $lead))
                            ->each->update(['owner_id' => auth()->id()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
