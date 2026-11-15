<?php

namespace App\Filament\Exports;

use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Shared behaviour for the CRM spreadsheet exports: neutralises values that
 * a spreadsheet would run as a formula (enquiry forms are public input) and
 * words the "export ready" bell notification.
 */
abstract class CrmExporter extends Exporter
{
    /** Plural noun used in the completed notification, e.g. "lead". */
    protected static string $recordNoun = 'record';

    /** @return array<mixed> */
    public function __invoke(Model $record): array
    {
        return array_map(static::neutraliseFormula(...), parent::__invoke($record));
    }

    /**
     * Prefix a quote to text that Excel/Sheets would evaluate (=, @, tab, CR,
     * or +/- followed by anything other than a plain number or phone number).
     */
    public static function neutraliseFormula(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        $isFormula = preg_match('/^[=@\t\r]/', $value) === 1
            || (preg_match('/^[+-]/', $value) === 1 && preg_match('/^[+-][\d\s().-]*$/', $value) !== 1);

        return $isFormula ? "'".$value : $value;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your '.static::$recordNoun.' export has completed and '.Str::of('row')->counted($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Str::of('row')->counted($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    /** Spreadsheet-friendly date (sorts correctly as text). */
    protected static function date(mixed $value, string $format = 'Y-m-d'): ?string
    {
        return $value?->format($format);
    }
}
