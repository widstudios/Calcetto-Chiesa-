<?php

namespace App\Imports;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\Training;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Un tipo di dato che si può caricare da Excel dal pannello (tornei, squadre, calciatori...).
 *
 * Ogni riga del foglio passa da parse(), che la controlla e la trasforma in dati pronti
 * da salvare, poi da save(). Rilanciare lo stesso file non duplica nulla: le righe già
 * presenti vengono aggiornate.
 */
abstract class Importer
{
    /** Tipi di import disponibili, nell'ordine in cui conviene caricarli. */
    public const TYPES = [
        'tornei' => TournamentImporter::class,
        'squadre' => TeamImporter::class,
        'allenatori' => CoachImporter::class,
        'calciatori' => PlayerImporter::class,
        'partite' => GameImporter::class,
        'allenamenti' => TrainingImporter::class,
    ];

    public static function for(string $type): ?self
    {
        $class = self::TYPES[$type] ?? null;

        return $class ? new $class : null;
    }

    /** Nome al plurale, es. "Calciatori". */
    abstract public function label(): string;

    /** Cosa fa l'import, in una frase. */
    abstract public function description(): string;

    /**
     * Colonne del foglio con la loro spiegazione; quelle obbligatorie finiscono con "*".
     *
     * @return array<string, string>
     */
    abstract public function columns(): array;

    /**
     * Righe di esempio per il modello da scaricare (dati inventati).
     *
     * @return list<list<string|int>>
     */
    abstract public function example(): array;

    /** Pagina del gestionale dove vedere i dati caricati. */
    abstract public function route(): string;

    /**
     * Controlla una riga e la trasforma nei dati da salvare.
     *
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     *
     * @throws RowError se la riga non è valida
     */
    abstract public function parse(array $row): array;

    /**
     * Descrizione della riga per l'anteprima.
     *
     * @param  array<string, mixed>  $data
     */
    abstract public function summary(array $data): string;

    /**
     * La riga corrisponde a un dato già presente, che verrà aggiornato.
     *
     * @param  array<string, mixed>  $data
     */
    abstract public function exists(array $data): bool;

    /**
     * Salva la riga; true se è stata aggiunta, false se esisteva già ed è stata aggiornata.
     *
     * @param  array<string, mixed>  $data
     */
    abstract public function save(array $data): bool;

    /**
     * Nomi delle colonne obbligatorie.
     *
     * @return list<string>
     */
    public function requiredColumns(): array
    {
        return array_values(array_map(
            fn (string $c) => rtrim($c, '*'),
            array_filter(array_keys($this->columns()), fn (string $c) => str_ends_with($c, '*')),
        ));
    }

    /**
     * Intestazioni del modello, senza asterischi.
     *
     * @return list<string>
     */
    public function headings(): array
    {
        return array_map(fn (string $c) => rtrim($c, '*'), array_keys($this->columns()));
    }

    /**
     * Valore obbligatorio di una colonna.
     *
     * @param  array<string, string>  $row
     */
    protected function required(array $row, string $column): string
    {
        $value = trim($row[$column] ?? '');
        if ($value === '') {
            throw new RowError("manca \"{$column}\".");
        }

        return $value;
    }

    /**
     * Nome scritto con le iniziali maiuscole (es. "DI MARTINO" diventa "Di Martino").
     */
    protected function name(string $value): string
    {
        return Str::title(Str::lower(preg_replace('/\s+/', ' ', trim($value))));
    }

    /**
     * Data come 28/09/2026 o 2026-09-28; null se vuota.
     */
    protected function date(string $value, string $column): ?string
    {
        $value = trim(Str::before($value, ' '));
        if ($value === '') {
            return null;
        }

        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $value, $m)) {
            [, $day, $month, $year] = $m;
        } elseif (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $value, $m)) {
            [, $year, $month, $day] = $m;
        } else {
            throw new RowError("\"{$column}\" non è una data valida (scrivi ad esempio 28/09/2026).");
        }

        if (! checkdate((int) $month, (int) $day, (int) $year)) {
            throw new RowError("\"{$column}\" non è una data valida (scrivi ad esempio 28/09/2026).");
        }

        return CarbonImmutable::create((int) $year, (int) $month, (int) $day)->format('Y-m-d');
    }

    /**
     * Orario come 18:30 (accetta anche 18.30 e 18); null se vuoto.
     */
    protected function time(string $value, string $column): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^(\d{1,2})(?:[:.](\d{2}))?(?::\d{2})?$/', $value, $m) || (int) $m[1] > 23 || (int) ($m[2] ?? 0) > 59) {
            throw new RowError("\"{$column}\" non è un orario valido (scrivi ad esempio 18:30).");
        }

        return sprintf('%02d:%02d', $m[1], $m[2] ?? 0);
    }

    /**
     * Numero intero tra $min e $max; null se la cella è vuota.
     */
    protected function integer(string $value, string $column, int $min, int $max): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (! ctype_digit($value) || (int) $value < $min || (int) $value > $max) {
            throw new RowError("\"{$column}\" deve essere un numero da {$min} a {$max}.");
        }

        return (int) $value;
    }

    /**
     * Giorno della settimana (lunedì, Martedi, mar...) come numero da 1 a 7.
     */
    protected function weekday(string $value): int
    {
        $day = Str::lower(Str::ascii(trim($value)));
        foreach (Training::WEEKDAYS as $number => $name) {
            $name = Str::lower(Str::ascii($name));
            if ($day !== '' && ($day === $name || (strlen($day) >= 3 && str_starts_with($name, $day)))) {
                return $number;
            }
        }

        throw new RowError('"giorno" non è un giorno della settimana (scrivi ad esempio martedì).');
    }

    /**
     * Torneo con quel nome (e stagione, se indicata); se ce ne sono più di uno, il più recente.
     */
    protected function findTournament(string $name, ?string $season): ?Tournament
    {
        return Tournament::whereRaw('lower(name) = ?', [Str::lower($name)])
            ->when($season, fn ($q) => $q->where('season', $season))
            ->latest('id')
            ->first();
    }

    protected function findTeam(?Tournament $tournament, string $name): ?Team
    {
        return $tournament?->teams()->whereRaw('lower(name) = ?', [Str::lower($name)])->first();
    }

    /**
     * Torneo esistente o, se manca, uno nuovo con quel nome e stagione.
     */
    protected function tournamentOrCreate(string $name, ?string $season): Tournament
    {
        return $this->findTournament($name, $season) ?? Tournament::create(['name' => $name, 'season' => $season]);
    }

    protected function teamOrCreate(Tournament $tournament, string $name): Team
    {
        return $this->findTeam($tournament, $name) ?? $tournament->teams()->create(['name' => $name]);
    }

    /**
     * Torneo e squadra indicati nelle colonne "torneo", "stagione" e "squadra".
     *
     * @param  array<string, string>  $row
     * @return array{torneo: string, stagione: ?string, squadra: string}
     */
    protected function teamColumns(array $row, string $teamColumn = 'squadra'): array
    {
        return [
            'torneo' => $this->required($row, 'torneo'),
            'stagione' => trim($row['stagione'] ?? '') ?: null,
            'squadra' => $this->required($row, $teamColumn),
        ];
    }

    /**
     * Squadra che deve già esistere nel gestionale.
     *
     * @param  array{torneo: string, stagione: ?string, squadra: string}  $data
     */
    protected function existingTeam(array $data): Team
    {
        $tournament = $this->findTournament($data['torneo'], $data['stagione']);
        if ($tournament === null) {
            throw new RowError("il torneo \"{$data['torneo']}\" non c'è: caricalo prima.");
        }

        return $this->findTeam($tournament, $data['squadra'])
            ?? throw new RowError("la squadra \"{$data['squadra']}\" non c'è nel torneo \"{$tournament->name}\": caricala prima.");
    }

    /**
     * Nome del torneo con la stagione, per l'anteprima.
     *
     * @param  array<string, mixed>  $data
     */
    protected function tournamentLabel(array $data): string
    {
        return $data['torneo'].($data['stagione'] ? " {$data['stagione']}" : '');
    }
}
