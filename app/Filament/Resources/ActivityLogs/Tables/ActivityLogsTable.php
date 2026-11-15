<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Filament\Support\ActivityHistory;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Trip;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    /** Columns searched on each logged subject type. */
    private const SUBJECT_SEARCH_COLUMNS = [
        Lead::class => ['reference'],
        Contact::class => ['first_name', 'last_name', 'email'],
        Company::class => ['name'],
        Quote::class => ['number', 'title'],
        Booking::class => ['number'],
        Payment::class => ['gateway_reference'],
        Trip::class => ['title', 'trip_code'],
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['causer', 'subject']))
            ->defaultSort('id', 'desc')
            ->recordAction('view')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->dateTimeTooltip('j M Y, H:i')
                    ->sortable(),
                TextColumn::make('causer')
                    ->label('Who')
                    ->state(fn (Activity $record): string => ActivityHistory::causerName($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph(
                        'causer',
                        [User::class],
                        fn (Builder $users): Builder => $users->where(fn (Builder $q): Builder => $q
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")),
                    )),
                TextColumn::make('subject_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => ActivityHistory::subjectTypeLabel($state)),
                TextColumn::make('subject')
                    ->label('Record')
                    ->state(fn (Activity $record): string => ActivityHistory::subjectLabel($record))
                    ->url(fn (Activity $record): ?string => ActivityHistory::subjectUrl($record))
                    ->color(fn (Activity $record): ?string => $record->subject ? 'primary' : 'gray')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph(
                        'subject',
                        array_keys(self::SUBJECT_SEARCH_COLUMNS),
                        function (Builder $subjects, string $type) use ($search): void {
                            $subjects->where(function (Builder $q) use ($type, $search): void {
                                foreach (self::SUBJECT_SEARCH_COLUMNS[$type] as $column) {
                                    $q->orWhere($column, 'like', "%{$search}%");
                                }
                            });
                        },
                    )),
                TextColumn::make('event')
                    ->badge()
                    ->formatStateUsing(fn (Activity $record): string => ActivityHistory::eventLabel($record))
                    ->color(fn (?string $state): string => ActivityHistory::eventColor($state)),
                TextColumn::make('changes')
                    ->label('Changed')
                    ->state(fn (Activity $record): string => ActivityHistory::summary($record))
                    ->placeholder('—')
                    ->limit(60)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('causer')
                    ->label('User')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('causer_type', (new User)->getMorphClass())->where('causer_id', $data['value']),
                    )),
                SelectFilter::make('subject_type')
                    ->label('Type')
                    ->options(ActivityHistory::SUBJECT_TYPES),
                SelectFilter::make('event')
                    ->options(ActivityHistory::EVENTS),
                Filter::make('created_at')
                    ->label('Date')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->where('created_at', '<=', Carbon::parse($date)->endOfDay())))
                    ->indicateUsing(fn (array $data): array => array_filter([
                        'from' => ($data['from'] ?? null) ? 'From '.Carbon::parse($data['from'])->format('j M Y') : null,
                        'until' => ($data['until'] ?? null) ? 'Until '.Carbon::parse($data['until'])->format('j M Y') : null,
                    ])),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (Activity $record): string => ActivityHistory::eventLabel($record).' · '.ActivityHistory::subjectLabel($record))
                    ->modalContent(fn (Activity $record) => view('filament.activity.entry', [
                        'activity' => $record,
                        'causer' => ActivityHistory::causerName($record),
                        'subject' => ActivityHistory::subjectLabel($record),
                        'subjectUrl' => ActivityHistory::subjectUrl($record),
                        'event' => ActivityHistory::eventLabel($record),
                        'eventColor' => ActivityHistory::eventColor($record->event),
                        'changes' => ActivityHistory::changes($record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ]);
    }
}
