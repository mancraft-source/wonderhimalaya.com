<?php

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportCsvAction(),
            $this->pasteListAction(),
            $this->importCsvAction(),
            CreateAction::make(),
        ];
    }

    protected function exportCsvAction(): Action
    {
        return Action::make('exportCsv')
            ->label('Export CSV')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['from', 'to', 'status', 'active', 'hits'], escape: '\\');
                Redirect::orderBy('from_path')->each(fn (Redirect $r) => fputcsv($out, [$r->from_path, $r->to_path, $r->status_code, $r->is_active ? 1 : 0, $r->hits], escape: '\\'));
                fclose($out);
            }, 'redirects-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']));
    }

    protected function pasteListAction(): Action
    {
        return Action::make('pasteList')
            ->label('Paste list')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color('gray')
            ->visible(fn (): bool => static::getResource()::canCreate())
            ->modalDescription('One redirect per line: "/old-path /new-path", optionally followed by 301, 302 or 410. Commas or tabs work too, so you can paste straight from a spreadsheet.')
            ->schema([
                Textarea::make('lines')
                    ->label('Redirects')
                    ->rows(10)
                    ->placeholder("/old-everest-trek /trip/everest-base-camp-trek\n/summer-offer /trips 302\n/discontinued-tour 410")
                    ->required(),
            ])
            ->action(function (array $data): void {
                $result = static::importLines((string) $data['lines']);

                Notification::make()
                    ->title("Imported {$result['imported']} redirects")
                    ->body($result['skipped'] ? "{$result['skipped']} lines skipped (missing or invalid paths)." : null)
                    ->success()
                    ->send();
            });
    }

    /** @return array{imported: int, skipped: int} */
    public static function importLines(string $lines, int $defaultStatus = 301): array
    {
        $imported = 0;
        $skipped = 0;

        foreach (preg_split('/\r\n|\r|\n/', $lines) as $line) {
            $parts = preg_split('/[\s,;]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);

            if ($parts === []) {
                continue;
            }

            $status = (int) end($parts);
            if (in_array($status, [301, 302, 410], true)) {
                array_pop($parts);
            } else {
                $status = $defaultStatus;
            }

            [$from, $to] = [$parts[0] ?? '', $parts[1] ?? ''];

            if ($from === '' || ($to === '' && $status !== 410)) {
                $skipped++;

                continue;
            }

            Redirect::point($from, $to === '' ? '/' : $to, $status);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
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
