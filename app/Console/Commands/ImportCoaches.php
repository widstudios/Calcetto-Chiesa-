<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carica l'elenco dei mister con il loro tesseramento CSI.
 *
 * Il file CSV ha una riga di intestazione con le colonne nome, email, tesserato_il
 * (separate da ";" o ","); tesserato_il è una data come 28/09/2026, vuota se il mister
 * è ancora da tesserare. Si può rilanciare: i mister già presenti (stessa email) non
 * vengono duplicati e una data già salvata non viene cancellata da una cella vuota.
 */
class ImportCoaches extends Command
{
    protected $signature = 'mister:importa {file : Percorso del file CSV con i mister}';

    protected $description = 'Carica i mister e il loro tesseramento CSI da un file CSV';

    public function handle(): int
    {
        $rows = $this->readCsv($this->argument('file'));
        if ($rows === null) {
            return self::FAILURE;
        }

        [$added, $updated] = DB::transaction(function () use ($rows) {
            $added = $updated = 0;
            foreach ($rows as $row) {
                $coach = User::whereRaw('lower(email) = ?', [$row['email']])->first();

                if ($coach === null) {
                    // Senza password nota: resta in elenco ma accede solo dopo che l'amministratore gliene imposta una.
                    User::create([
                        'name' => $row['nome'],
                        'email' => $row['email'],
                        'password' => Str::random(40),
                        'role' => Role::Allenatore,
                        'csi_registered_on' => $row['tesserato_il'],
                    ]);
                    $added++;
                } elseif ($row['tesserato_il'] !== null) {
                    $coach->update(['csi_registered_on' => $row['tesserato_il']]);
                    $updated++;
                }
            }

            return [$added, $updated];
        });

        $coaches = User::where('role', Role::Allenatore);
        $this->info("{$added} mister aggiunti, {$updated} aggiornati. Tesserati CSI: "
            .(clone $coaches)->whereNotNull('csi_registered_on')->count()
            .', da tesserare: '.$coaches->whereNull('csi_registered_on')->count().'.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{nome: string, email: string, tesserato_il: ?CarbonImmutable}>|null
     */
    private function readCsv(string $path): ?array
    {
        if (! is_readable($path)) {
            $this->error("File non trovato: {$path}");

            return null;
        }

        $lines = array_values(array_filter(
            preg_split('/\R/', preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($path))),
            fn (string $line) => trim($line) !== '',
        ));
        if ($lines === []) {
            $this->error('Il file è vuoto.');

            return null;
        }

        $separator = str_contains($lines[0], ';') ? ';' : ',';
        $header = array_map(fn ($h) => Str::lower(trim($h)), str_getcsv(array_shift($lines), $separator));
        foreach (['nome', 'email', 'tesserato_il'] as $column) {
            if (! in_array($column, $header, true)) {
                $this->error("Manca la colonna \"{$column}\" nella prima riga del file.");

                return null;
            }
        }

        $rows = [];
        foreach ($lines as $i => $line) {
            $values = array_map('trim', str_getcsv($line, $separator));
            $row = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
            $email = Str::lower($row['email']);
            $date = $this->parseDate($row['tesserato_il']);

            if ($row['nome'] === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $date === false) {
                $this->error('Riga '.($i + 2).' non valida: servono nome, email e una data come 28/09/2026 (o vuota).');

                return null;
            }
            $rows[] = ['nome' => $row['nome'], 'email' => $email, 'tesserato_il' => $date];
        }

        return $rows;
    }

    /**
     * Data nel formato 28/09/2026 (o 2026-09-28); null se la cella è vuota, false se non è valida.
     */
    private function parseDate(string $value): CarbonImmutable|false|null
    {
        if ($value === '') {
            return null;
        }

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            [, $day, $month, $year] = $m;
        } elseif (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value, $m)) {
            [, $year, $month, $day] = $m;
        } else {
            return false;
        }

        if (! checkdate((int) $month, (int) $day, (int) $year)) {
            return false;
        }
        $date = CarbonImmutable::create((int) $year, (int) $month, (int) $day);

        return $date->isFuture() ? false : $date;
    }
}
