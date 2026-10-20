<?php

namespace App\Filament\Resources\CrmTasks\Tables;

use App\Enums\TaskType;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Support\CrmFields;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\CrmTask;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CrmTasksTable
{
    public static function configure(Table $table, bool $withTaskable = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['assignee', ...($withTaskable ? ['taskable'] : [])]))
            ->defaultSort('due_at')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->description(fn (CrmTask $record): ?string => $record->description ? str($record->description)->limit(80)->toString() : null)
                    ->color(fn (CrmTask $record): ?string => $record->completed_at ? 'gray' : null),
                TextColumn::make('type')->badge(),
                ...($withTaskable ? [
                    TextColumn::make('taskable_type')
                        ->label('Related to')
                        ->state(fn (CrmTask $record): ?string => self::taskableLabel($record))
                        ->url(fn (CrmTask $record): ?string => self::taskableUrl($record))
                        ->placeholder('—'),
                ] : []),
                TextColumn::make('assignee.name')->label('Assignee')->placeholder('Unassigned'),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('D j M, H:i')
                    ->sortable()
                    ->color(fn (CrmTask $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->description(fn (CrmTask $record): ?string => $record->isOverdue() ? 'Overdue' : null),
                TextColumn::make('completed_at')->label('Done')->since()->placeholder('Open')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('open')
                    ->label('Status')
                    ->placeholder('All tasks')
                    ->trueLabel('Open')
                    ->falseLabel('Completed')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('completed_at'),
                        false: fn (Builder $q) => $q->whereNotNull('completed_at'),
                        blank: fn (Builder $q) => $q,
                    ),
                SelectFilter::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn () => CrmFields::staffOptions())
                    ->default($withTaskable ? auth()->id() : null),
                SelectFilter::make('type')->options(TaskType::class),
                TernaryFilter::make('overdue')
                    ->label('Overdue')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('completed_at')->where('due_at', '<', now()),
                        false: fn (Builder $q) => $q->where(fn (Builder $q) => $q->whereNotNull('completed_at')->orWhereNull('due_at')->orWhere('due_at', '>=', now())),
                    ),
            ])
            ->recordActions([
                self::completeAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('complete')
                        ->label('Mark complete')
                        ->icon(Heroicon::OutlinedCheck)
                        ->action(fn (Collection $records) => $records->each->update(['completed_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function completeAction(): Action
    {
        return Action::make('toggle_complete')
            ->label(fn (CrmTask $record): string => $record->completed_at ? 'Reopen' : 'Complete')
            ->icon(fn (CrmTask $record) => $record->completed_at ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedCheckCircle)
            ->color(fn (CrmTask $record): string => $record->completed_at ? 'gray' : 'success')
            ->authorize(fn (CrmTask $record): bool => auth()->user()->can('update', $record))
            ->action(fn (CrmTask $record) => $record->update(['completed_at' => $record->completed_at ? null : now()]));
    }

    public static function taskableLabel(CrmTask $task): ?string
    {
        return match (true) {
            $task->taskable instanceof Lead => 'Lead '.$task->taskable->reference,
            $task->taskable instanceof Contact => 'Contact '.$task->taskable->name,
            $task->taskable instanceof Booking => 'Booking '.$task->taskable->number,
            default => null,
        };
    }

    public static function taskableUrl(CrmTask $task): ?string
    {
        return match (true) {
            $task->taskable instanceof Lead => LeadResource::getUrl('view', ['record' => $task->taskable]),
            $task->taskable instanceof Contact => ContactResource::getUrl('edit', ['record' => $task->taskable]),
            $task->taskable instanceof Booking => BookingResource::getUrl('edit', ['record' => $task->taskable]),
            default => null,
        };
    }
}
