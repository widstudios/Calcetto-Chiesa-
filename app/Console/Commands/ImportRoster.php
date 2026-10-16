<?php

namespace App\Console\Commands;

use App\Enums\SquadRole;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\Training;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carica una categoria (es. "Under 14") con allenatori, iscritti e orari di allenamento.
 *
 * Il file CSV ha una riga di intestazione con le colonne cognome, nome, anno
 * (separate da ";" o ","), più le colonne facoltative cellulare1 e cellulare2.
 * Si può rilanciare: chi è già in rosa non viene duplicato (riceve solo i cellulari
 * scritti nel file) e gli orari vengono sostituiti da quelli passati.
 */
class ImportRoster extends Command
{
    protected $signature = 'rosa:importa
    {file : Percorso del file CSV con gli iscritti}
    {--categoria= : Nome della categoria, es. "Under 14" (diventa torneo e squadra)}
    {--stagione= : Stagione, es. 2026/27}
    {--allenatore= : Nome degli allenatori, es. "Piero Perilli, Simone Poggi"}
    {--allenamento=* : Orario nel formato "giorno|campo|inizio|fine", es. "martedì|Pescatori|18:30|20:00"}';

    protected $description = 'Carica una categoria con allenatori, iscritti e orari di allenamento da un file CSV';

    public function handle(): int
    {
        $category = trim((string) $this->option('categoria'));
        if ($category === '') {
            $this->error('Indica la categoria con --categoria="Under 14".');

            return self::FAILURE;
        }

        $rows = $this->readCsv($this->argument('file'));
        if ($rows === null) {
            return self::FAILURE;
        }

        $trainings = [];
        foreach ($this->option('allenamento') as $value) {
            $training = $this->parseTraining($value);
            if ($training === null) {
                $this->error("Allenamento non valido: \"{$value}\". Usa il formato \"martedì|Pescatori|18:30|20:00\".");

                return self::FAILURE;
            }
            $trainings[] = $training;
        }

        [$team, $added] = DB::transaction(function () use ($category, $rows, $trainings) {
            // Ogni stagione ha il suo torneo: rilanciare per una nuova stagione non tocca la precedente.
            $tournament = Tournament::firstOrCreate(['name' => $category, 'season' => $this->option('stagione')]);

            $team = Team::firstOrCreate(['tournament_id' => $tournament->id, 'name' => $category]);
            if ($this->option('allenatore')) {
                $team->update(['coach_name' => $this->option('allenatore')]);
            }

            $added = 0;
            foreach ($rows as $row) {
                $player = $team->players()->firstOrCreate(
                    ['first_name' => $row['nome'], 'last_name' => $row['cognome'], 'birth_year' => $row['anno']],
                    ['squad_role' => SquadRole::Titolare],
                );
                // Una cella vuota non cancella un cellulare già salvato.
                $player->update(array_filter(['phone' => $row['cellulare1'], 'phone_2' => $row['cellulare2']]));
                $added += $player->wasRecentlyCreated ? 1 : 0;
            }

            if ($trainings !== []) {
                $team->trainings()->delete();
                $team->trainings()->createMany($trainings);
            }

            return [$team, $added];
        });

        $this->info("{$category}: {$added} calciatori aggiunti, ".$team->players()->count().' in rosa, '.$team->trainings()->count().' allenamenti a settimana.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{cognome: string, nome: string, anno: int, cellulare1: ?string, cellulare2: ?string}>|null
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
        foreach (['cognome', 'nome', 'anno'] as $column) {
            if (! in_array($column, $header, true)) {
                $this->error("Manca la colonna \"{$column}\" nella prima riga del file.");

                return null;
            }
        }

        $rows = [];
        foreach ($lines as $i => $line) {
            $values = array_map('trim', str_getcsv($line, $separator));
            $row = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
            if ($row['cognome'] === '' || $row['nome'] === '' || ! ctype_digit($row['anno']) || $row['anno'] < 1940 || $row['anno'] > now()->year) {
                $this->error('Riga '.($i + 2).' non valida: servono cognome, nome e anno di nascita (es. 2013).');

                return null;
            }
            $phones = [];
            foreach (['cellulare1', 'cellulare2'] as $column) {
                $phone = preg_replace('/\s+/', ' ', $row[$column] ?? '');
                if ($phone !== '' && ! (strlen($phone) <= 30 && preg_match(Player::PHONE_PATTERN, $phone))) {
                    $this->error('Riga '.($i + 2)." non valida: \"{$column}\" deve essere un numero, es. 333 123 4567.");

                    return null;
                }
                $phones[$column] = $phone === '' ? null : $phone;
            }

            $rows[] = [
                'cognome' => Player::properName($row['cognome']),
                'nome' => Player::properName($row['nome']),
                'anno' => (int) $row['anno'],
            ] + $phones;
        }

        return $rows;
    }

    /**
     * @return array{weekday: int, field: string, starts_at: string, ends_at: string}|null
     */
    private function parseTraining(string $value): ?array
    {
        $parts = array_map('trim', explode('|', $value));
        if (count($parts) !== 4) {
            return null;
        }

        [$day, $field, $start, $end] = $parts;
        $weekday = array_search(Str::lower(Str::ascii($day)), array_map(fn ($d) => Str::lower(Str::ascii($d)), Training::WEEKDAYS), true);
        $time = '/^([01]\d|2[0-3]):[0-5]\d$/';

        if ($weekday === false || $field === '' || ! preg_match($time, $start) || ! preg_match($time, $end) || $end <= $start) {
            return null;
        }

        return ['weekday' => $weekday, 'field' => $field, 'starts_at' => $start, 'ends_at' => $end];
    }
}
