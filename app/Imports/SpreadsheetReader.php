<?php

namespace App\Imports;

use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Legge il primo foglio di un file Excel (.xlsx) o CSV e restituisce le righe come testo,
 * indicizzate con le intestazioni della prima riga (in minuscolo).
 */
class SpreadsheetReader
{
    /**
     * @return array{header: list<string>, rows: array<int, array<string, string>>} Le righe sono indicizzate col numero di riga del foglio.
     */
    public static function read(string $path, string $extension): array
    {
        $reader = self::reader($path, Str::lower($extension));
        $reader->open($path);

        $header = null;
        $rows = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $line => $row) {
                    $values = array_map(self::text(...), $row->toArray());
                    if (implode('', $values) === '') {
                        continue;
                    }
                    if ($header === null) {
                        $header = array_map(fn (string $h) => Str::of($h)->lower()->ascii()->replace(' ', '_')->toString(), $values);

                        continue;
                    }
                    $values = array_pad(array_slice($values, 0, count($header)), count($header), '');
                    $rows[$line] = array_combine($header, $values);
                }
                break; // Solo il primo foglio.
            }
        } finally {
            $reader->close();
        }

        return ['header' => $header ?? [], 'rows' => $rows];
    }

    private static function reader(string $path, string $extension): ReaderInterface
    {
        if ($extension === 'xlsx') {
            // Le righe vuote restano, così il numero di riga dei messaggi è quello che si vede in Excel.
            return new XlsxReader(new XlsxOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));
        }
        if (in_array($extension, ['csv', 'txt'], true)) {
            // Excel in italiano salva i CSV con il punto e virgola, e a volte non in UTF-8.
            $content = (string) file_get_contents($path);

            return new CsvReader(new CsvOptions(
                SHOULD_PRESERVE_EMPTY_ROWS: true,
                FIELD_DELIMITER: str_contains((string) strtok($content, "\n"), ';') ? ';' : ',',
                ENCODING: mb_check_encoding($content, 'UTF-8') ? 'UTF-8' : 'Windows-1252',
            ));
        }

        throw new RuntimeException('Formato non supportato: carica un file .xlsx o .csv.');
    }

    /**
     * Valore della cella come testo: le date diventano 28/09/2026, gli orari 18:30.
     */
    private static function text(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof DateInterval => sprintf('%02d:%02d', $value->h + $value->d * 24, $value->i),
            // Excel salva gli orari senza data come giorno 30/12/1899.
            $value instanceof DateTimeInterface && (int) $value->format('Y') < 1901 => $value->format('H:i'),
            $value instanceof DateTimeInterface && $value->format('H:i') === '00:00' => $value->format('d/m/Y'),
            $value instanceof DateTimeInterface => $value->format('d/m/Y H:i'),
            is_float($value) && floor($value) === $value => (string) (int) $value,
            is_bool($value) => $value ? '1' : '0',
            default => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)),
        };
    }
}
