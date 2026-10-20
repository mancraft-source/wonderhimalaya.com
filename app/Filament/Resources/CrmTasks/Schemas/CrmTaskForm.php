<?php

namespace App\Filament\Resources\CrmTasks\Schemas;

use App\Enums\TaskType;
use App\Filament\Support\CrmFields;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Lead;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CrmTaskForm
{
    public static function configure(Schema $schema, bool $withTaskable = true): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Select::make('type')->options(TaskType::class)->default(TaskType::Todo)->required(),
                DateTimePicker::make('due_at')->label('Due')->native(false)->seconds(false)->default(now()->addDay()->setTime(10, 0)),
                Select::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn () => CrmFields::staffOptions())
                    ->searchable()
                    ->default(fn () => auth()->id()),
                DateTimePicker::make('completed_at')->label('Completed')->native(false)->seconds(false),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                ...($withTaskable ? [
                    MorphToSelect::make('taskable')
                        ->label('Related to')
                        ->types([
                            MorphToSelect\Type::make(Lead::class)->titleAttribute('reference'),
                            MorphToSelect\Type::make(Contact::class)->titleAttribute('email'),
                            MorphToSelect\Type::make(Booking::class)->titleAttribute('number'),
                        ])
                        ->searchable()
                        ->columnSpanFull(),
                ] : []),
            ]);
    }
}
