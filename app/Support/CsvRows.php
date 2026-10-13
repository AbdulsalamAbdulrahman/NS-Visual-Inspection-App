<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reads an uploaded CSV made from one of our templates: the first row names
 * the columns (any order, any case), blank rows are skipped, cells trimmed.
 */
final class CsvRows
{
    /**
     * @param  list<string>  $columns  required column names (snake_case)
     * @return list<array{line: int, values: array<string, string>}>
     *
     * @throws RuntimeException when the file doesn't match the template
     */
    public static function read(string $path, array $columns, int $maxRows): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('The file could not be read.');
        }

        try {
            $header = fgetcsv($handle, escape: '');
            // Excel adds a byte-order mark to "CSV UTF-8" files.
            $header = is_array($header) ? array_map(fn ($h): string => Str::snake(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header) : [];
            $missing = array_diff($columns, $header);

            if ($missing !== []) {
                throw new RuntimeException('Use the template: the first row must have the column names. Missing: '.implode(', ', $missing).'.');
            }

            $rows = [];
            $line = 1;

            while (($cells = fgetcsv($handle, escape: '')) !== false) {
                $line++;
                $cells = array_map(fn ($cell): string => trim((string) $cell), $cells);

                if (implode('', $cells) === '') {
                    continue;
                }

                if (count($rows) >= $maxRows) {
                    throw new RuntimeException("Upload at most {$maxRows} rows at a time.");
                }

                $rows[] = ['line' => $line, 'values' => array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), ''))];
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw new RuntimeException('The file has no rows under the column names.');
        }

        return $rows;
    }

    /** Lower-case letters and digits only, for matching names and labels. */
    public static function key(string $text): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower($text)) ?? '';
    }
}
