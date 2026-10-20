<?php

namespace App\Filament\RelationManagers;

use App\Filament\Resources\CrmTasks\Schemas\CrmTaskForm;
use App\Filament\Resources\CrmTasks\Tables\CrmTasksTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Follow-up tasks attached to a CRM record via the `taskable` morph. */
class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedCheckCircle;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return CrmTaskForm::configure($schema, withTaskable: false);
    }

    public function table(Table $table): Table
    {
        return CrmTasksTable::configure($table, withTaskable: false)
            ->headerActions([
                CreateAction::make()
                    ->label('Add task')
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by_id'] = auth()->id();
                        $data['assignee_id'] ??= auth()->id();

                        return $data;
                    }),
            ]);
    }
}
