<?php

namespace App\Filament\Resources\NotFoundLogs\Pages;

use App\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use App\Models\NotFoundLog;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListNotFoundLogs extends ListRecords
{
    protected static string $resource = NotFoundLogResource::class;

    public function getSubheading(): ?string
    {
        return 'Broken URLs visitors and search engines reached. Redirect the ones that matter; ignore bots and junk.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearIgnored')
                ->label('Clear ignored')
                ->icon(Heroicon::OutlinedTrash)
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn () => NotFoundLog::where('is_ignored', true)->delete()),
        ];
    }
}
