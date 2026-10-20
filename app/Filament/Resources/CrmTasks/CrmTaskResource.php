<?php

namespace App\Filament\Resources\CrmTasks;

use App\Filament\Resources\CrmTasks\Pages\CreateCrmTask;
use App\Filament\Resources\CrmTasks\Pages\EditCrmTask;
use App\Filament\Resources\CrmTasks\Pages\ListCrmTasks;
use App\Filament\Resources\CrmTasks\Schemas\CrmTaskForm;
use App\Filament\Resources\CrmTasks\Tables\CrmTasksTable;
use App\Models\CrmTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CrmTaskResource extends Resource
{
    protected static ?string $model = CrmTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Tasks';

    protected static ?string $modelLabel = 'task';

    public static function getNavigationBadge(): ?string
    {
        $count = CrmTask::query()
            ->where('assignee_id', auth()->id())
            ->whereNull('completed_at')
            ->where('due_at', '<', now())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'My overdue tasks';
    }

    public static function form(Schema $schema): Schema
    {
        return CrmTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmTasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmTasks::route('/'),
            'create' => CreateCrmTask::route('/create'),
            'edit' => EditCrmTask::route('/{record}/edit'),
        ];
    }
}
