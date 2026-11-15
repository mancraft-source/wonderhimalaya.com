<?php

namespace App\Filament\Support;

use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Exporter;

/**
 * CSV / XLSX export for CRM tables. Exports run on the queue and land in the
 * admin bell with a download link. Only users who may list the resource
 * (its policy's viewAny, e.g. payments.manage for payments) can export.
 */
final class ExportActions
{
    /** @param  class-string<Exporter>  $exporter  Exports every row matching the table's current search and filters. */
    public static function header(string $exporter): ExportAction
    {
        return ExportAction::make()
            ->exporter($exporter)
            ->color('gray')
            ->authorize(fn (): bool => self::canExport($exporter));
    }

    /** @param  class-string<Exporter>  $exporter  Exports the selected rows. */
    public static function bulk(string $exporter): ExportBulkAction
    {
        return ExportBulkAction::make()
            ->exporter($exporter)
            ->authorize(fn (): bool => self::canExport($exporter));
    }

    /** @param  class-string<Exporter>  $exporter */
    public static function canExport(string $exporter): bool
    {
        return (bool) auth()->user()?->can('viewAny', $exporter::getModel());
    }
}
