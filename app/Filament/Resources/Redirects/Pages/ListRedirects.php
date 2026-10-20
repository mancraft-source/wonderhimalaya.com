<?php

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->importCsvAction(),
            CreateAction::make(),
        ];
    }

    protected function importCsvAction(): Action
    {
        return Action::make('importCsv')
            ->label('Import CSV')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->visible(fn (): bool => static::getResource()::canCreate())
            ->modalDescription('One redirect per row: from,to[,status]. A header row is skipped automatically. Existing redirects with the same "from" are updated and chains are collapsed.')
            ->schema([
                FileUpload::make('file')
                    ->label('CSV file')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->storeFiles(false)
                    ->required(),
                Select::make('default_status')
                    ->label('Status when not given')
                    ->options([301 => '301', 302 => '302', 410 => '410'])
                    ->default(301)
                    ->selectablePlaceholder(false),
            ])
            ->action(function (array $data): void {
                /** @var TemporaryUploadedFile $file */
                $file = $data['file'];
                $result = static::importCsv($file->getRealPath(), (int) $data['default_status']);

                Notification::make()
                    ->title("Imported {$result['imported']} redirects")
                    ->body($result['skipped'] ? "{$result['skipped']} rows skipped (missing or invalid paths)." : null)
                    ->success()
                    ->send();
            });
    }

    /** @return array{imported: int, skipped: int} */
    public static function importCsv(string $path, int $defaultStatus = 301): array
    {
        $imported = 0;
        $skipped = 0;
        $handle = fopen($path, 'r');

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $from = trim((string) ($row[0] ?? ''));
            $to = trim((string) ($row[1] ?? ''));

            if ($from === '' || in_array(strtolower($from), ['from', 'from_path', 'source'], true)) {
                if ($from === '' && $to === '') {
                    continue;
                }
                $skipped += $from === '' ? 1 : 0;

                continue;
            }

            $status = (int) ($row[2] ?? 0);
            $status = in_array($status, [301, 302, 410], true) ? $status : $defaultStatus;

            if ($to === '' && $status !== 410) {
                $skipped++;

                continue;
            }

            Redirect::point($from, $to === '' ? '/' : $to, $status);
            $imported++;
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
