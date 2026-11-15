<?php

namespace App\Support\System;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Reads the most recent error-level entries from the Laravel log files
 * (single `laravel.log` or daily `laravel-YYYY-MM-DD.log`), looking only
 * at the tail of each file so it stays fast however big the logs grow.
 */
class RecentErrors
{
    /** Levels treated as errors (Monolog's names, lower-cased). */
    public const LEVELS = ['error', 'critical', 'alert', 'emergency'];

    private const ENTRY_START = '/^\[(\d{4}-\d{2}-\d{2}[ T][0-9:.]+(?:[+-]\d{2}:?\d{2}|Z)?)\]\s+[\w-]+\.([A-Z]+):\s?(.*)$/';

    public function __construct(
        private string $directory,
        private int $bytesPerFile = 524288,
        private int $maxFiles = 3,
    ) {}

    public static function fromStorage(): self
    {
        return new self(storage_path('logs'));
    }

    /**
     * @return list<array{time: ?Carbon, level: string, message: string, trace: string, file: string}>
     */
    public function latest(int $limit = 20): array
    {
        $entries = [];

        foreach ($this->logFiles() as $path) {
            $fileEntries = $this->errorsIn($path);

            // Newest first within each file, files themselves newest first.
            foreach (array_reverse($fileEntries) as $entry) {
                $entries[] = $entry;

                if (count($entries) >= $limit) {
                    return $entries;
                }
            }
        }

        return $entries;
    }

    /** @return list<string> */
    private function logFiles(): array
    {
        $files = glob(rtrim($this->directory, '/').'/*.log') ?: [];

        usort($files, fn (string $a, string $b): int => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));

        return array_slice($files, 0, $this->maxFiles);
    }

    /** @return list<array{time: ?Carbon, level: string, message: string, trace: string, file: string}> */
    private function errorsIn(string $path): array
    {
        $entries = [];
        $current = null;

        foreach (preg_split('/\R/', $this->tail($path)) ?: [] as $line) {
            if (preg_match(self::ENTRY_START, $line, $match)) {
                if ($current !== null) {
                    $entries[] = $current;
                }

                $level = strtolower($match[2]);
                $current = in_array($level, self::LEVELS, true) ? [
                    'time' => $this->parseTime($match[1]),
                    'level' => $level,
                    'message' => Str::limit(trim($match[3]), 500),
                    'trace' => '',
                    'file' => basename($path),
                ] : null;

                continue;
            }

            if ($current !== null && strlen($current['trace']) < 8000) {
                $current['trace'] .= $line."\n";
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return array_map(function (array $entry): array {
            $entry['trace'] = rtrim($entry['trace']);

            return $entry;
        }, $entries);
    }

    /** The last $bytesPerFile bytes of the file, starting at a line boundary. */
    private function tail(string $path): string
    {
        $size = filesize($path) ?: 0;
        $handle = @fopen($path, 'rb');

        if (! $handle || $size === 0) {
            return '';
        }

        $offset = max(0, $size - $this->bytesPerFile);
        fseek($handle, $offset);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        if ($offset > 0 && ($newline = strpos($content, "\n")) !== false) {
            $content = substr($content, $newline + 1);
        }

        return $content;
    }

    private function parseTime(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
