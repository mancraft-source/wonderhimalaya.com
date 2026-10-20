<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CrmTasks\Tables\CrmTasksTable;
use App\Models\CrmTask;
use App\Support\Permissions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MyOpenTasks extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permissions::CRM_VIEW);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('My open tasks')
            ->query(fn () => CrmTask::query()
                ->with('taskable')
                ->where('assignee_id', auth()->id())
                ->whereNull('completed_at'))
            ->defaultSort('due_at')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nothing on your list')
            ->columns([
                TextColumn::make('title')->wrap(),
                TextColumn::make('type')->badge(),
                TextColumn::make('related')
                    ->label('Related to')
                    ->state(fn (CrmTask $record): ?string => CrmTasksTable::taskableLabel($record))
                    ->url(fn (CrmTask $record): ?string => CrmTasksTable::taskableUrl($record))
                    ->placeholder('—'),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('D j M, H:i')
                    ->sortable()
                    ->color(fn (CrmTask $record): ?string => $record->isOverdue() ? 'danger' : null),
            ])
            ->recordActions([
                CrmTasksTable::completeAction(),
            ]);
    }
}
