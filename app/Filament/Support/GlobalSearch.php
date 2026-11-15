<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Helpers for the panel's global search (⌘K): phone-number matching that ignores
 * spaces/+/dashes, and the one-line "a · b · c" result previews.
 */
final class GlobalSearch
{
    /** Results shown per resource, so one busy resource can't crowd out the rest. */
    public const RESULTS_LIMIT = 8;

    private const PHONE_SEPARATORS = [' ', '+', '-', '(', ')', '.'];

    /**
     * The digits of a search term that looks like a phone number
     * (only digits and phone punctuation, at least five digits), otherwise null.
     */
    public static function phoneDigits(string $search): ?string
    {
        $search = trim($search);

        if (! preg_match('/^[\d\s+\-().]+$/', $search)) {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', $search);

        return strlen($digits) >= 5 ? $digits : null;
    }

    /**
     * Match records whose phone columns contain the digits, ignoring punctuation in the stored value.
     *
     * @param  array<int, string>  $columns  Columns on the model, or "relation.column".
     */
    public static function wherePhoneMatches(Builder $query, array $columns, string $digits): Builder
    {
        return $query->where(function (Builder $query) use ($columns, $digits): void {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    $query->orWhereHas(
                        (string) str($column)->beforeLast('.'),
                        fn (Builder $related): Builder => self::whereDigits($related, (string) str($column)->afterLast('.'), $digits),
                    );

                    continue;
                }

                self::whereDigits($query, $column, $digits, 'or');
            }
        });
    }

    /**
     * A single preview line: the filled parts joined with " · ".
     *
     * @param  array<int, string|null>  $parts
     * @return array<int, string>
     */
    public static function line(array $parts): array
    {
        $line = implode(' · ', array_filter($parts, fn (?string $part): bool => filled($part)));

        return $line === '' ? [] : [$line];
    }

    public static function money(float|string|null $amount, ?string $currency): ?string
    {
        if ($amount === null) {
            return null;
        }

        return ($currency ?: 'USD').' '.number_format((float) $amount, fmod((float) $amount, 1.0) === 0.0 ? 0 : 2);
    }

    private static function whereDigits(Builder $query, string $column, string $digits, string $boolean = 'and'): Builder
    {
        $expression = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));

        foreach (self::PHONE_SEPARATORS as $separator) {
            $expression = "REPLACE({$expression}, '{$separator}', '')";
        }

        return $query->whereRaw("{$expression} LIKE ?", ['%'.$digits.'%'], $boolean);
    }
}
